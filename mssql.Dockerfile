# syntax=docker/dockerfile:1
FROM mcr.microsoft.com/mssql/server:2022-latest@sha256:d252932ef839c24c61c1139cc98f69c85ca774fa7c6bfaaa0015b7eb02b9dc87 
USER root

RUN export DEBIAN_FRONTEND=noninteractive && \
apt-get update --fix-missing && \
apt-get install -y gnupg2 && \
apt-get install -yq curl apt-transport-https && \
curl https://packages.microsoft.com/keys/microsoft.asc | tac | tac | apt-key add - && \
curl https://packages.microsoft.com/config/ubuntu/22.04/mssql-server-2022.list | tac | tac | tee /etc/apt/sources.list.d/mssql-server.list && \
apt-get update

RUN apt-get install -y mssql-server-fts

RUN sed -i -E 's/(CipherString\s*=\s*DEFAULT@SECLEVEL=)2/\10/' /etc/ssl/openssl.cnf

# Run SQL Server process
CMD ["/opt/mssql/bin/sqlservr"]
