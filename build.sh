#!/usr/bin/env bash
#
# Setup api.keyman.com site to run via Docker.
#
set -eu

## START STANDARD BUILD SCRIPT INCLUDE
# adjust relative paths as necessary
THIS_SCRIPT="$(greadlink -f "${BASH_SOURCE[0]}" 2>/dev/null || readlink -f "${BASH_SOURCE[0]}")"
. "$(dirname "$THIS_SCRIPT")/resources/builder.inc.sh"
## END STANDARD BUILD SCRIPT INCLUDE

################################ Main script ################################

function _get_docker_image_id() {
  echo "$(docker images -q api-keyman-website)"
}

function _get_docker_container_id() {
  echo "$(docker ps -a -q --filter ancestor=api-keyman-website)"
}

function _stop_docker_container() {
  API_CONTAINER=$(_get_docker_container_id)
  if [ ! -z "$API_CONTAINER" ]; then
    docker container stop $API_CONTAINER
    docker container stop api-keyman-com-database
  else
    echo "No Docker container to stop"
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
  # Setup DB

  
  builder_finish_action success configure
fi

if builder_start_action clean; then
  # Stop and cleanup Docker containers and images used for the site
  _stop_docker_container

  API_CONTAINER=$(_get_docker_container_id)
  if [ ! -z "$API_CONTAINER" ]; then
    docker container rm $API_CONTAINER
  else
    echo "No Docker container to clean"
  fi
    
  API_IMAGE=$(_get_docker_image_id)
  if [ ! -z "$API_IMAGE" ]; then
    docker rmi api-keyman-website
  else 
    echo "No Docker image to clean"
  fi

  builder_finish_action success clean
fi

if builder_start_action stop; then
  # Stop the Docker container
  _stop_docker_container
  builder_finish_action success stop
fi

if builder_start_action build; then
  # Download docker image. --mount option requires BuildKit  
  DOCKER_BUILDKIT=1 docker build -t api-keyman-website .

  builder_finish_action success build
fi

if builder_start_action start; then
  # Start the Docker container
  DB_DUCKY=true

  if [ ! -z $(_get_docker_image_id) ]; then
    # Setup database
    #docker run -e "ACCEPT_EULA=Y" -e "MSSQL_SA_PASSWORD=yourStrong(!)Password" -e "MSSQL_PID=standard" -p 8058:1433 -d api-keyman-website
    #builder_finish_action success start
    #exit
    # debugging - finish early


    if [[ $OSTYPE =~ msys|cygwin ]]; then
      # Windows needs leading slashes for path
      docker run -e "ACCEPT_EULA=Y" -e "MSSQL_SA_PASSWORD=yourStrong(!)Password" -p 8058:1433 -d mcr.microsoft.com/mssql/server:2022-latest

      # TODO: sort out ports to run DB and site
      #docker run -d -e "ACCEPT_EULA=Y" -e "MSSQL_SA_PASSWORD=yourStrong(!)Password" -e "MSSQL_PID=standard" -p 8058:1433 -p 8058:80 -v //$(pwd):/var/www/html/ -e S_KEYMAN_COM=localhost:8054 api-keyman-website
      #docker run -d -e "ACCEPT_EULA=Y" -e "MSSQL_SA_PASSWORD=yourStrong(!)Password" -e "MSSQL_PID=standard" --expose 1433 --expose 80 -p 8058 -v //$(pwd):/var/www/html/ -e S_KEYMAN_COM=localhost:8054 api-keyman-website
    else
      if [ $DB_DUCKY == true ]; then
        echo "Setting up DB"
        #docker run -e "ACCEPT_EULA=Y" -e "MSSQL_SA_PASSWORD=yourStrong(!)Password" -p 8058:80 -d mcr.microsoft.com/mssql/server:2022-latest
        docker run -d -p 8099:1433 \
          -e "ACCEPT_EULA=Y" \
          -e "MSSQL_SA_PASSWORD=yourStrong(!)Password" \
          --name 'api-keyman-com-database' \
          mcr.microsoft.com/mssql/server:2022-latest #api-keyman-database #mcr.microsoft.com/mssql/server:2022-latest 

        # TODO: sort out ports to run DB and site
        # docker run -d -e "ACCEPT_EULA=Y" -e "MSSQL_SA_PASSWORD=yourStrong(!)Password" -e "MSSQL_PID=standard" --expose 1433 --expose 80 -p 8058 -v $(pwd):/var/www/html/ -e S_KEYMAN_COM=localhost:8054 api-keyman-website
        #else
        echo "Spooling up site"
        docker run -d -p 8098:80 -v $(pwd):/var/www/html/ \
          -e S_KEYMAN_COM=localhost:8054 \
          -e 'api_keyman_com_mssql_pw=yourStrong(!)Password' \
          -e api_keyman_com_mssql_user=sa \
          -e 'api_keyman_com_mssqlconninfo=sqlsrv:Server=localhost,8099;Database=' \
          -e api_keyman_com_mssql_create_database=true \
          -e api_keyman_com_mssqldb=keyboards \
          --name 'api-keyman-com' \
          api-keyman-website

        echo "Site attempting to connect to DB"
        docker exec -i api-keyman-com sh -c "php /var/www/html/tools/db/build/build_cli.php"
      fi
    fi
  else
    echo "${COLOR_RED}ERROR: Docker container doesn't exist. Run ./build.sh build first${COLOR_RESET}"
    builder_finish_action fail start
  fi

  # Skip if link already exists
  if [ -L vendor ]; then
    echo "\nLink to vendor/ already exists"
  else
    # Create link to vendor/ folder
    API_CONTAINER=$(_get_docker_container_id)
    echo "API_CONTAINER: ${API_CONTAINER}"
    if [ ! -z "$API_CONTAINER" ]; then
      echo "making link"
      docker exec -i $API_CONTAINER sh -c "ln -s /var/www/vendor vendor && chown -R www-data:www-data vendor"
    else
      echo "No Docker container running to create link to vendor/"
    fi
  fi

  builder_finish_action success start
fi

if builder_start_action test; then
  # TODO: lint tests

  #composer check-docker-links

  builder_finish_action success test
fi