# SynergyCP WHMCS Integration

### Setup
 - Download this directory to a machine with [Composer](https://getcomposer.org/) installed
 - Run `composer install`
 - (Optionally:) Run the tests `phpunit`
 - Copy the entire directory via FTP, SCP, etc. to `/WHMCS_PATH/modules/servers/synergycp/`
 - Go to Synergy. Create an API Key, and copy the key.
 - Go to WHMCS Admin panel.
 - Go to Setup (Top nav) > Products/Services > Servers
 - Add New Server
   - Name: Synergy
   - Hostname: <link to SynergyCP API>
   - Scroll down to Server Details
   - Type: Synergy Control Panel
   - Access Hash: <API Key>
 - Go to Setup (Top nav) > Products/Services > Products/Services
 - Click Create a New Product
   - Product Type: Dedicated/VPS Server
   - Module Settings > Module Name: Synergy Control Panel
