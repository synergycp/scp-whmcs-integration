<hr />

<div class="row">
  <div class="col-sm-5 text-right">
    <strong>IP Allocation</strong>
  </div>
  <div class="col-sm-7 text-left">
    {foreach from=$ips item=ip}
      <strong>{$ip->name}</strong><br />
      - Usable IP(s): {$ip->full_ip}<br />
      - Gateway IP: {$ip->gateway}<br />
      - Subnet Mask: {$ip->subnet_mask}<br /><br />
    {/foreach}
  </div>
</div>

<div class="row">
  <div class="col-sm-5 text-right">
    <strong>Bandwidth Usage</strong>
  </div>
  <div class="col-sm-7 text-left">
    <div class="progress">
      <div class="progress-bar" role="progressbar"
        aria-valuenow="2" aria-valuemin="0" aria-valuemax="100"
        style="min-width: 2em; width: 90%;">
        90%
      </div>
    </div>
    9.01 of 10TB used -
    <a target="_blank" href="{$url_action}btn_manage">
      view graph</a>
    <br /><br />
  </div>
</div>

{if $server->ipmi_access}
  <div class="row">
    <div class="col-sm-5 text-right">
      <strong>IPMI Details</strong>
    </div>
    <div class="col-sm-7 text-left">
      <a href="http://{$server->ipmi_ip}" target="_blank">{$server->ipmi_ip}</a><br />
      - Username: {$server->ipmi_client_user|default:'None'}
      (<a href="{$url_action}{if $server->ipmi_client_user}btn_ipmi_client_delete{else}btn_ipmi_client_create{/if}"
        >{if $server->ipmi_client_user}Delete{else}Create{/if}</a>)<br />
      - Password: {$server->ipmi_client_pass|default:'None'}<br />
      <br />
    </div>
  </div>
{/if}

<div class="row">
  <div class="col-sm-5 text-right">
    <strong>OS Reload</strong>
  </div>
  <div class="col-sm-7 text-left">
    <div class="form-group">
      <label><strong>Operating System</strong></label>
      <select class="form-control">
        <option value="">Select an Operating System...</option>
      </select>
    </div>
    <div class="form-group">
      <label><strong>Edition</strong></label>
      <select class="form-control">
        <option value="">Select an Edition...</option>
      </select>
    </div>
    <div class="form-group">
      <label><strong>License Key</strong></label>
      <input type="text" name="license_key" class="form-control" placeholder="Leave blank for trial" />
    </div>
    <input type="submit" class="btn btn-success" value="Install Operating System" />
    <br /><br />
  </div>
</div>

<hr />

<form method="post" action="{$url_action}">
  <input type="hidden" name="a" value="btn_manage" />
  <input type="submit" value="Manage on SynergyCP" class="btn btn-info" />
</form>
