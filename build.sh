#!/usr/bin/env bash
#
# Setup api.keyman.com site to run via Docker.
#
set -eu

## START STANDARD BUILD SCRIPT INCLUDE
# adjust relative paths as necessary
THIS_SCRIPT="$(readlink -f "${BASH_SOURCE[0]}")"
. "${THIS_SCRIPT%/*}/resources/builder.inc.sh"
## END STANDARD BUILD SCRIPT INCLUDE

################################ Main script ################################

# Get the docker image ID.
# Default to api-keyman-website if :db not specified
function _get_docker_image_id() {
  IMAGE_NAME="api-keyman-website"

  if [[ "$#" > 0 && "$1" == ":db" ]]; then
    IMAGE_NAME="api-keyman-database"
  fi

  echo "$(docker images -q ${IMAGE_NAME})"
}

# Get the Docker container ID.
# Default to api-keyman-website if :db not specified
function _get_docker_container_id() {
  ANCESTOR="api-keyman-website"

  if [[ "$#" > 0 && "$1" == ":db" ]]; then
    ANCESTOR="api-keyman-database"
  fi

  echo "$(docker ps -a -q --filter ancestor=${ANCESTOR})"
}

function _stop_docker_container() {
  if [[ "$#" > 0 && "$1" == ":db" ]]; then
    API_CONTAINER=$(_get_docker_container_id :db)
    CONTAINER_NAME="api-keyman-com-database"
  else
    API_CONTAINER=$(_get_docker_container_id :app)
    CONTAINER_NAME="api-keyman-com"
  fi

  if [ ! -z "$API_CONTAINER" ]; then
    docker container stop ${CONTAINER_NAME}
  else
    echo "No Docker $1 container to stop"
  fi
}

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

if builder_start_action configure; then
  
  builder_finish_action success configure
fi

if builder_start_action clean; then
  # Stop and cleanup Docker containers and images used for the site
  _stop_docker_container :db
  _stop_docker_container :app

  API_IMAGE=$(_get_docker_image_id :db)
  if [ ! -z "$API_IMAGE" ]; then
    docker rmi api-keyman-database
  else 
    builder_echo "No Docker database image to clean"
  fi

  API_IMAGE=$(_get_docker_image_id)
  if [ ! -z "$API_IMAGE" ]; then
    docker rmi api-keyman-website
  else 
    builder_echo "No Docker app image to clean"
  fi

  builder_finish_action success clean
fi

if builder_start_action stop:db; then
  # Stop the Docker database container
  _stop_docker_container :db
  builder_finish_action success stop:db
fi

if builder_start_action stop:app; then
  # Stop the Docker app container
  _stop_docker_container :app
  builder_finish_action success stop:app
fi

if builder_start_action build:db; then
  # Download docker image. --mount option requires BuildKit  
  DOCKER_BUILDKIT=1 docker build -t api-keyman-database -f mssql.Dockerfile .

  builder_finish_action success build:db
fi

if builder_start_action build:app; then
  # Download docker image. --mount option requires BuildKit  
  DOCKER_BUILDKIT=1 docker build -t api-keyman-website .

  builder_finish_action success build:app
fi

if builder_start_action start:db; then
  # Start the Docker database container

  if [ ! -z $(_get_docker_image_id :db) ]; then
    # Setup database
    builder_echo "Setting up DB container"
    docker run --rm -d -p 8099:1433 \
      -e "ACCEPT_EULA=Y" \
      -e "MSSQL_AGENT_ENABLED=true" \
      -e "MSSQL_SA_PASSWORD=yourStrong(\!)Password" \
      --name 'api-keyman-com-database' \
      api-keyman-database

  else
    builder_echo error "ERROR: Docker database container doesn't exist. Run ./build.sh build first"
    builder_finish_action fail start:db
  fi

  builder_finish_action success start:db
fi

if builder_start_action start:app; then
  # Start the Docker site container

  if [ ! -z $(_get_docker_image_id :app) ]; then
    if [[ $OSTYPE =~ msys|cygwin ]]; then
      # Windows needs leading slashes for path
      SITE_HTML="//$(pwd):/var/www/html/"
    else
      SITE_HTML="$(pwd):/var/www/html/"
    fi

    db_ip=$(docker inspect -f '{{range.NetworkSettings.Networks}}{{.IPAddress}}{{end}}' api-keyman-com-database)

    builder_echo "Spooling up site container"
    docker run --rm -d -p 8098:80 -v ${SITE_HTML} \
      -e S_KEYMAN_COM=localhost:8054 \
      -e 'api_keyman_com_mssql_pw=yourStrong(\\!)Password' \
      -e api_keyman_com_mssql_user=sa \
      -e 'api_keyman_com_mssqlconninfo=sqlsrv:Server='$db_ip',1433;TrustServerCertificate=true;Encrypt=false;Database=' \
      -e api_keyman_com_mssql_create_database=true \
      -e api_keyman_com_mssqldb=keyboards \
      --name 'api-keyman-com' \
      api-keyman-website

  else
    builder_echo error "ERROR: Docker site container doesn't exist. Run ./build.sh build first"
    builder_finish_action fail start:db
  fi

  # Skip if link already exists
  if [ -L vendor ]; then
    builder_echo "\nLink to vendor/ already exists"
  else
    # Create link to vendor/ folder
    API_CONTAINER=$(_get_docker_container_id)
    builder_echo "API_CONTAINER: ${API_CONTAINER}"
    if [ ! -z "$API_CONTAINER" ]; then
      builder_echo "making link"
      docker exec -i $API_CONTAINER sh -c "ln -s /var/www/vendor vendor && chown -R www-data:www-data vendor"
    else
      builder_echo "No Docker container running to create link to vendor/"
    fi
  fi

  sleep 15;
  builder_echo "Sleep 15 before attempting to connect to DB"
  docker exec -i api-keyman-com sh -c "php /var/www/html/tools/db/build/build_cli.php"

  builder_finish_action success start:app
fi

if builder_start_action test; then
  # TODO: lint tests

  builder_finish_action success test
fi
