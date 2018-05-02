### Setup

 1. Download and extract the WHMCS integration [here](https://install.synergycp.com/bm/integration/whmcs.tgz)
 2. Copy the entire directory via FTP, SCP, etc. to `/<WHMCS_PATH>/modules/servers/synergycp/`
 3. Go to SynergyCP Admin > System > Integrations.
 4. Add an Integration for WHMCS.
 5. Edit the Integration and make sure it has the following permissions:
     - Clients (View & Edit)
     - Installs (View & Edit)
     - Servers In Use (View & Edit)
     - Servers In Inventory (View & Edit)
     - IP Entities (View)
     - IP Groups (View)
 6. Create an API Key for the Integration, and copy the key.
 7. Go to WHMCS Admin > Setup (Top nav) > Products/Services > Servers
 8. Add New Server
     - Name: SynergyCP
     - Hostname: The hostname of the SynergyCP API - this should start with `api.`
 9. Scroll down to Server Details
     - Type: Synergy Control Panel
     - Access Hash: <API Key of SynergyCP Integration>

### Adding a New Product

1. Go to Setup (Top nav) > Products/Services > Products/Services > Create a New Product
2. Product Type: Dedicated/VPS Server
3. Product Name: The CPU name (e.g. E3-1270v6, Dual E5-2620v4)
4. Module Settings:
    - Module Name: Synergy Control Panel
    - Fill in CPU Billing ID from SynergyCP
    - We recommend format-quick as the Pre-OS install. Formats are required before some OS reloads to get rid of the old disk partition table.
 
### Configurable Options

Configurable Options use the billing ID on WHMCS to link up with the billing ID on SynergyCP.

The billing ID is specified before the value shown to the user separated by a Unix pipe character:
 
![selection](https://user-images.githubusercontent.com/229041/30526732-a3009a72-9bd4-11e7-9a83-cf2f963f490c.png)
 
Every SynergyCP product on WHMCS must include the following Configurable Options (names must match exactly):

1. Memory (RAM)
2. Datacenter Location (IP Group)
3. Network Port Speed (Switch Port Speed)
4. IPv4 Addresses (IP Entity)
5. Add On 1, Add On 2, etc. (any number).
    - Use ADD-RAID1 and ADD-RAID0 as billing IDs for automatic software RAID configuration. 
6. Drive Bay 1, Drive Bay 2, etc. (any number). The value should be None for empty disk bays.

Optional configuration options:
1. Bandwidth (e.g. 500GB, 20TB) 
