# SynergyCP WHMCS Integration

### Setup

 - Download and extract the WHMCS integration from: [SynergyCP](https://install.synergycp.com/bm/integration/whmcs.tgz)
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
   - Configurable Options should include some named: Add On 1, Add On 2, etc. and SSD Bay 1, SSD Bay 2, etc.
