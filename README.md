# SynergyCP WHMCS Integration

WHMCS provisioning module for [SynergyCP](https://synergycp.com), the dedicated server control panel. It provisions servers from SynergyCP inventory, keeps clients in sync, reports bandwidth usage, and gives clients server controls inside the WHMCS client area.

## Documentation

Setup, configuration, and usage documentation lives in the SynergyCP knowledge base:

**https://kb.synergycp.com/docs/integrations/whmcs/**

That covers installing the module, linking a WHMCS server to SynergyCP, configuring products and configurable options, and troubleshooting.

## Download

The latest release is published as a zip:

https://install.synergycp.com/bm/integration/whmcs/synergycp.zip

Extract it to `/<WHMCS_PATH>/modules/servers/synergycp/`, then follow the knowledge base guide to finish setup.

## Requirements

- WHMCS 8 or later
- PHP 8.3 or later

## Development

See [DEVELOPMENT.md](DEVELOPMENT.md) for cloning, running the test suite, and building the distribution package.
