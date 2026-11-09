## Cloning this repo

Make sure to clone recursively to get the submodules:

```bash
git clone --recurse-submodules <repo-url>
```

## Requirements

The module targets PHP 8.3 or later. The scripts under `bin/` run everything inside a Docker image built from `bin/Dockerfile`, so a local PHP install is not required.

## Running the tests

Install Docker, then run this from the root directory of this repo:

```bash
./bin/run-tests-in-docker.sh
```

This installs dependencies (including dev tools) for both the module and the `Scp/` library, then runs both PHPUnit suites.

To run a single test locally with PHP 8.3 and dependencies installed:

```bash
./vendor/bin/phpunit --filter testMethodName
```

## Building the distribution package

```bash
./bin/export.sh
```

This builds a production-only zip and uploads it to R2. It needs the R2 credentials described at the top of the script.
