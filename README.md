# api.keyman.com

This is the source for the website https://api.keyman.com/, which hosts the
database backend for Keyman websites. This site runs on Apache in a Docker
container, and the database itself runs on SQL Server for Linux in a separate
container.

## Other Keyman websites

* **[api.keyman.com]** - database backend for Keyman websites
* **[help.keyman.com]** - documentation home for Keyman
* **[keyman.com]** - Keyman home
* **[keymanweb.com]** - KeymanWeb online keyboard
* **[s.keyman.com]** - static Javascript, font, and related resources
* **[website-local-proxy]** - run all Keyman sites on localhost on the same port

## How to run api.keyman.com locally

When run locally, this site can be accessed at http://localhost:8058 or
http://api.keyman.com.localhost:8058.

**Recommended:** Use [website-local-proxy] to run multiple keyman.com sites
all from the same port (default port 80).

**Recommended:** Use [shared-sites] to control startup and shutdown of all
keyman.com sites together.

### Prerequisites

The host machine needs the following apps installed:
* [Git]
* Bash 5.x (on Windows, you can use Git Bash that comes with [Git])
* [Docker Desktop]

  <details>
  <summary>Configuration of Docker on Windows</summary>

    On Windows machines, you can setup Docker in two different ways, either of
    which should work:
    * [Enable Hyper-V on Windows 11](https://techcommunity.microsoft.com/t5/educator-developer-blog/step-by-step-enabling-hyper-v-for-use-on-windows-11/ba-p/3745905)
    * [WSL2](https://ubuntu.com/tutorials/install-ubuntu-on-wsl2-on-windows-10#1-overview)

  </details>

### Actions

#### Build the Docker image

The first time you want to start up the site, or if there have been Docker
configuration changes, you will need to rebuild the Docker images. Start a bash
shell, and from this folder, run:

```sh
./build.sh build
```

#### Start the Docker container

To start up the website, in bash, run:

```sh
./build.sh start --debug
```

Once the container starts, you can access the api.keyman.com site at
http://localhost:8058 or http://api.keyman.com.localhost:8058

#### Stop the Docker container

In bash, run:

```sh
./build.sh stop
```

#### Remove the Docker container and image

In bash, run:

```sh
./build.sh clean
```

#### Running tests

Test suites run with mock data from the tests/data folder. To check APIs, broken
links and .php file conformance, when the site is running, in bash, run:

```sh
./build.sh test
```

To force a rebuild of the test database from the mock data (for example if
schema changes and this is not automatically detected):

```sh
./build.sh test --rebuild-test-fixtures
```

[Git]: https://git-scm.com/downloads
[Docker Desktop]: https://docs.docker.com/get-docker/
[api.keyman.com]: https://github.com/keymanapp/api.keyman.com
[help.keyman.com]: https://github.com/keymanapp/help.keyman.com
[keyman.com]: https://github.com/keymanapp/keyman.com
[keymanweb.com]: https://github.com/keymanapp/keymanweb.com
[s.keyman.com]: https://github.com/keymanapp/s.keyman.com
[website-local-proxy]: https://github.com/keymanapp/website-local-proxy
[shared-sites]: https://github.com/keymanapp/shared-sites
[enable Hyper-V]: https://techcommunity.microsoft.com/t5/educator-developer-blog/step-by-step-enabling-hyper-v-for-use-on-windows-11/ba-p/3745905
[enable WSL2]: https://ubuntu.com/tutorials/install-ubuntu-on-wsl2-on-windows-10#1-overview

