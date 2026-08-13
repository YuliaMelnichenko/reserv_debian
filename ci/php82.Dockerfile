FROM php:8.2-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates git nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /workspace
