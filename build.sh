#!/usr/bin/env bash
## START STANDARD SITE BUILD SCRIPT INCLUDE
readonly THIS_SCRIPT="$(readlink -f "${BASH_SOURCE[0]}")"
readonly BOOTSTRAP="$(dirname "$THIS_SCRIPT")/resources/bootstrap.inc.sh"
readonly BOOTSTRAP_VERSION=v1.08
[ -f "$BOOTSTRAP" ] && source "$BOOTSTRAP" || source <(curl -fs https://raw.githubusercontent.com/keymanapp/shared-sites/$BOOTSTRAP_VERSION/bootstrap.inc.sh)
## END STANDARD SITE BUILD SCRIPT INCLUDE

readonly API_KEYMAN_DB_CONTAINER_NAME=api-keyman-com-db
readonly API_KEYMAN_DB_CONTAINER_DESC=api-keyman-com-db
readonly API_KEYMAN_DB_IMAGE_NAME=api-keyman-com-db

readonly API_KEYMAN_CONTAINER_NAME=api-keyman-com-website
readonly API_KEYMAN_CONTAINER_DESC=api-keyman-com-app
readonly API_KEYMAN_IMAGE_NAME=api-keyman-com-website
readonly HOST_API_KEYMAN_COM=api.keyman.com.localhost

source _common/keyman-local-ports.inc.sh
source _common/docker.inc.sh

################################ Main script ################################

builder_describe \
  "Setup api.keyman.com site to run via Docker." \
  "configure" \
  "clean" \
  "build" \
  "start" \
  "stop" \
  "test" \
  "--rebuild-test-fixtures   Rebuild the test fixtures from live data" \
  ":db   Build the database" \
  ":app  Build the site"

builder_parse "$@"

function test_docker_container() {
  echo "TIER_TEST" > tier.txt
  # Note: ci.yml replicates these

  if builder_has_option --rebuild-test-fixtures; then
    touch rebuild-test-fixtures.txt
  fi

  # Run unit tests
  docker exec $API_KEYMAN_CONTAINER_DESC sh -c "vendor/bin/phpunit --testdox"

  # Lint .php files for obvious errors
  docker exec $API_KEYMAN_CONTAINER_DESC sh -c "find . -name '*.php' | grep -v '/vendor/' | xargs -n 1 -d '\\n' php -l"

  # Check all internal links
  # NOTE: link checker runs on host rather than in docker image
  npx broken-link-checker http://localhost:8058 --ordered --recursive --host-requests 10 -e --filter-level 3

  rm tier.txt
}

builder_run_action configure bootstrap_configure

builder_run_action clean:db   clean_docker_container $API_KEYMAN_DB_IMAGE_NAME $API_KEYMAN_DB_CONTAINER_NAME
builder_run_action clean:app  clean_docker_container $API_KEYMAN_IMAGE_NAME $API_KEYMAN_CONTAINER_NAME
builder_run_action stop:db    stop_docker_container  $API_KEYMAN_DB_IMAGE_NAME $API_KEYMAN_DB_CONTAINER_NAME
builder_run_action stop:app   stop_docker_container  $API_KEYMAN_IMAGE_NAME $API_KEYMAN_CONTAINER_NAME

# Build the Docker containers
function build_docker_container_db() {
  local IMAGE_NAME=$1
  local CONTAINER_NAME=$2

  # Download docker image. --mount option requires BuildKit
  DOCKER_BUILDKIT=1 docker build -t $API_KEYMAN_DB_IMAGE_NAME -f mssql.Dockerfile .
}

builder_run_action build:db   build_docker_container_db $API_KEYMAN_DB_IMAGE_NAME $API_KEYMAN_DB_CONTAINER_NAME
builder_run_action build:app  build_docker_container   $API_KEYMAN_IMAGE_NAME $API_KEYMAN_CONTAINER_NAME

# Custom start actions for db and app different from shared-sites
function start_docker_container_db() {
  local IMAGE_NAME=$1
  local CONTAINER_NAME=$2
  local CONTAINER_DESC=$3
  # HOST not applicable
  local PORT=$4

  local CONTAINER_ID=$(get_docker_container_id $CONTAINER_NAME)
  if [ ! -z "$CONTAINER_ID" ]; then
    builder_echo green "SQL Server already started, listening on localhost:$PORT"
    return 0
  fi

  # Start the Docker container
  if [ -z $(get_docker_image_id $IMAGE_NAME) ]; then
    builder_echo yellow "Docker db container doesn't exist. Running \"./build.sh build:db\" first"
    "$THIS_SCRIPT" build:db
    builder_echo green "Docker db container has been created successfully"
  fi

  # Setup database
  builder_echo "Setting up DB container"
  docker run --rm -d -p $PORT:1433 \
    -e "ACCEPT_EULA=Y" \
    -e "MSSQL_AGENT_ENABLED=true" \
    -e "MSSQL_SA_PASSWORD=yourStrong(\!)Password" \
    --name $CONTAINER_DESC \
    $CONTAINER_NAME

  builder_echo "Sleeping for 30 seconds to give database time to spin up"
  builder_echo "(DB may crash if connected to, too early, on some systems)"
  sleep 30s

  builder_echo green "SQL Server Listening on localhost:$PORT"
}

function start_docker_container_app() {
  local IMAGE_NAME=$1
  local CONTAINER_NAME=$2
  local CONTAINER_DESC=$3
  local HOST=$4
  local PORT=$5

  _verify_vendor_is_not_folder

  local CONTAINER_ID=$(get_docker_container_id $CONTAINER_NAME)
  if [ ! -z "$CONTAINER_ID" ]; then
    builder_echo green "Container $CONTAINER_ID has already been started, listening on http://$HOST:$PORT"
    return 0
  fi

  # Start the Docker container
  if [ -z $(get_docker_image_id $IMAGE_NAME) ]; then
    builder_echo yellow "Docker app container doesn't exist. Running \"./build.sh build:app\" first"
    "$THIS_SCRIPT" build:app
    builder_echo green "Docker app container has been created successfully"
  fi

  if [[ $OSTYPE =~ msys|cygwin ]]; then
    # Windows needs leading slashes for path
    SITE_HTML="//$(pwd):/var/www/html/"
  else
    SITE_HTML="$(pwd):/var/www/html/"
  fi

  ADD_HOST=
  if [[ $OSTYPE =~ linux-gnu ]]; then
    # Linux needs --add-host parameter
    ADD_HOST="--add-host host.docker.internal:host-gateway"
  fi

  builder_echo "Checking network settings"

  db_ip=$(docker inspect -f '{{range.NetworkSettings.Networks}}{{.IPAddress}}{{end}}' ${API_KEYMAN_DB_IMAGE_NAME})

  builder_echo "Spooling up site container"

  docker run --rm -m 200m -d -p $PORT:80 -v ${SITE_HTML} \
    -e 'api_keyman_com_mssql_pw=yourStrong(\!)Password' \
    -e api_keyman_com_mssql_user=sa \
    -e 'api_keyman_com_mssqlconninfo=sqlsrv:Server='$db_ip',1433;TrustServerCertificate=true;Encrypt=false;Database=' \
    -e api_keyman_com_mssql_create_database=true \
    -e api_keyman_com_mssqldb=keyboards \
    --name $CONTAINER_DESC \
    ${ADD_HOST} \
    $CONTAINER_NAME

  # Skip if link already exists
  if [ ! -L vendor ]; then
    # Create link to vendor/ folder
    CONTAINER_ID=$(get_docker_container_id $CONTAINER_NAME)
    if [ -z "$CONTAINER_ID" ]; then
      builder_die "Docker container appears to have failed to start in order to create link to vendor/"
    fi

    docker exec -i $CONTAINER_ID sh -c "ln -s /var/www/vendor vendor && chown -R www-data:www-data vendor"
  fi

  # after starting container, we want to run an init script if it is present
  if [ -f resources/init-container.sh ]; then
    CONTAINER_ID=$(get_docker_container_id $CONTAINER_NAME)
    if [ -z "$CONTAINER_ID" ]; then
      builder_die "Docker container appears to have failed to start in order to run init-container.sh script"
    fi

    docker exec -i $CONTAINER_ID sh -c "./resources/init-container.sh"
  fi

  builder_echo green "Listening on http://$HOST:$PORT"
}

builder_run_action start:db   start_docker_container_db  $API_KEYMAN_DB_IMAGE_NAME $API_KEYMAN_DB_CONTAINER_NAME $API_KEYMAN_DB_CONTAINER_DESC $PORT_API_KEYMAN_COM_DB
builder_run_action start:app  start_docker_container_app $API_KEYMAN_IMAGE_NAME $API_KEYMAN_CONTAINER_NAME $API_KEYMAN_CONTAINER_DESC $HOST_API_KEYMAN_COM $PORT_API_KEYMAN_COM

builder_run_action test:app      test_docker_container
