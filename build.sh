#!/usr/bin/env bash
## START STANDARD BUILD SCRIPT INCLUDE
# adjust relative paths as necessary
THIS_SCRIPT="$(readlink -f "${BASH_SOURCE[0]}")"
. "${THIS_SCRIPT%/*}/resources/builder.inc.sh"
## END STANDARD BUILD SCRIPT INCLUDE

################################ Main script ################################

builder_describe "Setup api.keyman.com site to run via Docker." \
  "configure" \
  "clean" \
  "build" \
  "start" \
  "stop" \
  "test" \
  ":db   Build the database" \
  ":app  Build the site"

builder_parse "$@"

# This script runs from its own folder
cd "$REPO_ROOT"

declare -A DOCKER_IMAGE DOCKER_CONTAINER
DOCKER_IMAGE[app]=api-keyman-com-app
DOCKER_IMAGE[db]=api-keyman-com-db
DOCKER_CONTAINER[app]=${DOCKER_IMAGE[app]}
DOCKER_CONTAINER[db]=${DOCKER_IMAGE[db]}

# Get the docker image ID for input parameter
function _get_docker_image_id() {
  echo "$(docker images -q ${DOCKER_IMAGE[$1]})"
}

# Get the Docker container ID for input parameter
function _get_docker_container_id() {
  echo "$(docker ps -a -q --filter ancestor=${DOCKER_CONTAINER[$1]})"
}

function _stop_docker_container() {
  local API_CONTAINER=$(_get_docker_container_id $1)
  local CONTAINER_NAME=${DOCKER_CONTAINER[$1]}

  if [ ! -z "$API_CONTAINER" ]; then
    docker container stop ${CONTAINER_NAME}
  else
    builder_echo "No Docker $1 container to stop"
  fi
}

function _delete_docker_image() {
  builder_echo "Stopping running container for $1"
  _stop_docker_container $1
  local API_IMAGE=$(_get_docker_image_id $1)
  if [ ! -z "$API_IMAGE" ]; then
    builder_echo "Removing image $API_IMAGE for $1"
    docker rmi "$API_IMAGE"
  else
    builder_echo "No Docker $1 image to delete"
  fi
}

builder_run_action configure # no action

# Stop and cleanup Docker containers and images used for the site

builder_run_action clean:db _delete_docker_image db
builder_run_action clean:app _delete_docker_image app

# Stop the Docker containers
builder_run_action stop:db _stop_docker_container db
builder_run_action stop:app _stop_docker_container app

# Build the Docker containers
if builder_start_action build:db; then
  # Download docker image. --mount option requires BuildKit
  DOCKER_BUILDKIT=1 docker build -t ${DOCKER_IMAGE[db]} -f mssql.Dockerfile .
  builder_finish_action success build:db
fi

if builder_start_action build:app; then
  # Download docker image. --mount option requires BuildKit
  DOCKER_BUILDKIT=1 docker build -t ${DOCKER_CONTAINER[app]} .
  builder_finish_action success build:app
fi

if builder_start_action start:db; then
  # Start the Docker database container

  if [ ! -z $(_get_docker_image_id db) ]; then
    # Setup database
    builder_echo "Setting up DB container"
    docker run --rm -d -p 8059:1433 \
      -e "ACCEPT_EULA=Y" \
      -e "MSSQL_AGENT_ENABLED=true" \
      -e "MSSQL_SA_PASSWORD=yourStrong(\!)Password" \
      --name ${DOCKER_IMAGE[db]} \
      ${DOCKER_CONTAINER[db]}
  else
    builder_echo error "ERROR: Docker database container doesn't exist. Run ./build.sh build first"
    builder_finish_action fail start:db
  fi

  builder_finish_action success start:db
fi

if builder_start_action start:app; then
  # Start the Docker site container

  if [ -d vendor ]; then
    builder_die "vendor folder is in the way. Please delete it"
  fi

  if [ ! -z $(_get_docker_image_id app) ]; then
    if [[ $OSTYPE =~ msys|cygwin ]]; then
      # Windows needs leading slashes for path
      SITE_HTML="//$(pwd):/var/www/html/"
    else
      SITE_HTML="$(pwd):/var/www/html/"
    fi

    db_ip=$(docker inspect -f '{{range.NetworkSettings.Networks}}{{.IPAddress}}{{end}}' ${DOCKER_IMAGE[db]})

    builder_echo "Spooling up site container"
    docker run --rm -d -p 8058:80 -v ${SITE_HTML} \
      -e 'api_keyman_com_mssql_pw=yourStrong(\!)Password' \
      -e api_keyman_com_mssql_user=sa \
      -e 'api_keyman_com_mssqlconninfo=sqlsrv:Server='$db_ip',1433;TrustServerCertificate=true;Encrypt=false;Database=' \
      -e api_keyman_com_mssql_create_database=true \
      -e api_keyman_com_mssqldb=keyboards \
      --name ${DOCKER_IMAGE[app]} \
      ${DOCKER_CONTAINER[app]}

  else
    builder_echo error "ERROR: Docker site container doesn't exist. Run ./build.sh build first"
    builder_finish_action fail start:db
  fi

  # Skip if link already exists
  if [ -L vendor ]; then
    builder_echo "\nLink to vendor/ already exists"
  else
    # TODO: handle vendor/ folder in the way
    # Create link to vendor/ folder
    builder_echo "making link for vendor/ folder"
    docker exec -i ${DOCKER_IMAGE[app]} sh -c "ln -s /var/www/vendor vendor && chown -R www-data:www-data vendor"
  fi

  sleep 15;
  builder_echo "Sleep 15 before attempting to connect to DB"
  docker exec -i ${DOCKER_IMAGE[app]} sh -c "php /var/www/html/tools/db/build/build_cli.php"

  builder_finish_action success start:app
fi

if builder_start_action test:app; then
  docker exec -i ${DOCKER_IMAGE[app]} sh -c "php /var/www/html/vendor/bin/phpunit --testdox"
  builder_finish_action success test:app
fi
