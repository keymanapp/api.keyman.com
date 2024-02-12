# syntax=docker/dockerfile:1
FROM mcr.microsoft.com/mssql/server:2022-latest@sha256:ffef32dda16cc5abd70db1d0654c01cbe9f7093d66e0c83ade20738156cb7d0e
USER root

RUN export DEBIAN_FRONTEND=noninteractive && \
apt-get update --fix-missing && \
apt-get install -y gnupg2 && \
apt-get install -yq curl apt-transport-https && \
curl https://packages.microsoft.com/keys/microsoft.asc | tac | tac | apt-key add - && \
curl https://packages.microsoft.com/config/ubuntu/22.04/mssql-server-2022.list | tac | tac | tee /etc/apt/sources.list.d/mssql-server.list && \
apt-get update

RUN apt-get install -y mssql-server-fts

# Run SQL Server process
CMD /opt/mssql/bin/sqlservr
