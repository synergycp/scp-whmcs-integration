{if $embed}
  <iframe src="{$embedUrl}" width="100%" height="1000">
    <p>iFrames are not supported by your browser.</p>
  </iframe>
{else}
  {* This CSS is important for proper rendering of hidden elements on the page, so must be at the top. *}
  <link type="text/css" rel="stylesheet" href="{$WEB_ROOT}{$MODULE_FOLDER}/assets/base.css" />

  {*
  {if count($ips) > 1}
  <div class="row">
    <div class="col-sm-5 text-right">
      <strong>Extra IP Allocations</strong>
    </div>
    <div class="col-sm-7 text-left">
      {foreach from=$ips item=ip key=index}
        {if $index > 0}
          <strong>{$ip->name}</strong><br />
          - Usable IP(s): {$ip->full_ip}<br />
          - Gateway IP: {$ip->gateway}<br />
          - Subnet Mask: {$ip->subnet_mask}<br />
          {if $ip->v6_address}
            - IPv6 Address: {$ip->v6_address}<br />
            - IPv6 Gateway: {$ip->v6_gateway}<br />
          {/if}
          <br />
        {/if}
      {/foreach}
    </div>
  </div>
  {/if}
  *}

  <div class="row">
    <div class="col-sm-5 text-right">
      <strong>Bandwidth Usage</strong>
    </div>
    <div class="col-sm-7 text-left">
      {if $bandwidth.limit}
        <div class="progress">
          <div class="progress-bar" role="progressbar"
               aria-valuenow="2" aria-valuemin="0" aria-valuemax="100"
               style="min-width: 2em; width: {$bandwidth.percent}%;">
            {$bandwidth.percent}%
          </div>
        </div>
        {$bandwidth.used} of {$bandwidth.limit} used
      {else}
        {$bandwidth.used} used
      {/if} -
      <a target="_blank" href="{$url_action}btn_manage">
        view graph</a>
      <br /><br />
    </div>
  </div>

  {if $server->access->ipmi && $server->access->is_active && $server->ipmi}
    <div class="row">
      <div class="col-sm-5 text-right">
        <strong>IPMI Details</strong>
      </div>
      <div class="col-sm-7 text-left">
        <a href="http://{$server->ipmi->ip}" target="_blank">{$server->ipmi->ip}</a><br />
        - Username: {$server->ipmi->client->username|default:'None'}
        (<a href="{$url_action}{if $server->ipmi->client->username}btn_ipmi_client_delete{else}btn_ipmi_client_create{/if}"
        >{if $server->ipmi->client->username}Delete{else}Create{/if}</a>)<br />
        - Password: {$server->ipmi->client->password|default:'None'}<br />
        <br />
      </div>
    </div>
  {/if}

  {if $server->access->pxe && $server->access->is_active}
    <div class="row" id="scp-pxe-status">
      <div class="col-sm-5 text-right">
        <strong>OS Reload</strong>
      </div>
      <div class="col-sm-7 text-left">
        <div id="scp-pxe-installing" style="display:none">
          <div class="progress">
            <div class="progress-bar active progress-bar-striped" role="progressbar"
                 aria-valuenow="2" aria-valuemin="0" aria-valuemax="100"
                 style="min-width: 2em; width: 0%;" id="scp-pxe-install-progress">
              0%
            </div>
          </div>
          Installing: <span id="scp-pxe-install-name"></span><br />
          Status: <span id="scp-pxe-install-status"></span><br />
          <a href="#" id="scp-pxe-install-cancel">Cancel Installation</a>
        </div>

        <form class="scp-no-iso" id="scp-os-reload" style="display:none">
          <div class="form-group">
            <label><strong>Operating System</strong></label>
            <select class="form-control" id="scp-os-choice">
              <option value="">Select an Operating System...</option>
            </select>
          </div>
          <div class="form-group scp-has-iso-only">
            <label><strong>Edition</strong></label>
            <select class="form-control" id="scp-edition-choice">
              <option value="">Select an Edition...</option>
            </select>
          </div>
          <div class="form-group scp-has-iso-only">
            <label><strong>License Key</strong></label>
            <input type="text" name="license_key" id="scp-license-key" class="form-control"
                   placeholder="Leave blank for trial" />
          </div>
          <div class="form-group">
            <label><strong>Password</strong></label>
            <input type="text" name="password" id="scp-password" class="form-control"
                   value="{$password}"/>
          </div>
          <button type="submit" class="btn btn-success scp-relative">
            <b class="scp-loader"><b></b></b>
            Install Operating System
          </button>
        </form>
        <br /><br />
      </div>
    </div>
  {/if}

  {if $manage}
    <hr />

    <form method="post" action="{$url_action}">
      <input type="hidden" name="a" value="btn_manage" />
      <input type="submit" value="Manage on SynergyCP" class="btn btn-info" />
    </form>
    <br />
  {/if}

  <script type="text/javascript" src="{$WEB_ROOT}{$MODULE_FOLDER}/assets/base.js"></script>
  <script type="text/javascript" src="{$WEB_ROOT}{$MODULE_FOLDER}/assets/client-area.js"></script>
  <script type="text/javascript">
    SCP.init({
      key: "{$apiKey}",
      url: "{$apiUrl}"
    });

    SCP.ClientArea.init({
      server_id: {$server->id}
    });
  </script>
{/if}
