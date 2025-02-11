# SandPIM

SandPIM is a LAMP-based Product Information Management system built around the AutoCare Association's ACES and PIES standards. The "Sand" in the name is a reference to the Sandpiper protocol that is starting to take shape in the AutoCare community. SandPIM serves a platform for testing concepts core to Sandpiper as they are being debated before adoption. SandPIM is intentionally written with minimal layers of abstraction and without a third-party framework. This is to lower the barriers to entry for a casual experimenter or contributor.

SandPIM is published under the MIT License model.

This project is in the early stages. An organization using it will need in-house (php) developer talent to adapt it to the business and (hopefully) contribute their code back to the public repo.
AutoPartSource is using SandPIM as its production PIM for managing our brakes, filters and exhaust product offerings (AmeriBRAKES, AirQualitee, Original Exhaust and private-label programs). 
We publish tools like this because it helps us stay connected with the community to stay current with technology trends in our industry. If you are interested in
contributing in any way (even just offering opinions!), please don't hesitate to reach out.

---

## Features

- Catalog fitment management based on Make-Model-Year and/or Mfr-Equipment
- Digital Asset management
- Part attribute (PAdb) support
- Qualifier (Qdb) support
- Pricesheet management
- Competitor interchange management
- On-the-fly validation of data
- ACES & PIES xml exports
- ACES & PIES xml imports
- Sandpiper API server (primary and secondary roles)
- VIO & PIO support

--- 


## To-Do list (currently being worked on as-of Q2, 2022)

- Inheritance of attributes, applications and assets from base parts
- Deployment how-to document for Fedora Linux


---


## Docker images for demo server

There are a pair of images on DockerHub for easily running a pre-configured demonstration of SandPIM without setting up a LAMP environment from scratch:

https://hub.docker.com/repository/docker/autopartsource/sandpimdemo-webservice
https://hub.docker.com/repository/docker/autopartsource/sandpimdemo-database

The webservice container and database container must be run together for the server to function. The easiest way to do this is with docker-compose.

Create a docker-compose.yml file with this inside:

    version: '3.7'
    services:
        sandpimdb:
            image: autopartsource/sandpimdemo-database
            ports:
                - 3306:3306
            environment:
                TZ: "America/New_York"
                MYSQL_ROOT_PASSWORD: 'sandpim'
                MYSQL_ALLOW_EMPTY_PASSWORD: 'no'
            restart: always
        sandpimweb:
            image: autopartsource/sandpimdemo-webservice
            ports:
                - 80:80
            depends_on:
                - sandpimdb
            links:
                - sandpimdb
            restart: always

Start the pair of containers by running the command (in the same directory as your docker-compose.yml file):

    docker-compose up

You should be able to browse to http://localhost/login.php where you will find setup instructions. If you get a "connection refused" error, wait a minute and refresh the browser page. The database service takes about a minute to process the initial database setup.