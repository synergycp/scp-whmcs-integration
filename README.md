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

### Adding a New Product

- Product Type: Dedicated/VPS Server
- Product Name: The CPU (e.g. E3-1270v6, Dual E5-2620v4)
- Module Settings:
 - Module Name: Synergy Control Panel
 - Fill in CPU Billing ID from SynergyCP
 - We recommend format-quick as the Pre-OS install.
- Configurable Options use the billing ID on WHMCS to link up with the billing ID on SynergyCP.

  The billing ID is specified before the value shown to the user separated by a Unix pipe character:
 
  ![selection](https://user-images.githubusercontent.com/229041/30526732-a3009a72-9bd4-11e7-9a83-cf2f963f490c.png)
 
  Options must include (and be called):
   - Memory (RAM)
   - Datacenter Location (IP Group)
   - Network Port Speed (Switch Port Speed)
   - IPv4 Addresses (IP Entity)
   - Add On 1, Add On 2, etc. (any number)
   - SSD Bay 1, SSD Bay 2, etc. (any number)     
