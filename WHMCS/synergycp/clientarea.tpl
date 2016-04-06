<hr />

<div class="row">
  <div class="col-sm-5 text-right">
    <strong>IP Allocation</strong>
  </div>
  <div class="col-sm-7 text-left">
    {foreach from=$ips item=ip}
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
    9.01 of 10TB used.
  </div>
</div>

<hr />

<form method="post" action="clientarea.php?action=productdetails">
    <input type="hidden" name="id" value="{$serviceid}" />
    <input type="hidden" name="modop" value="custom" />
    <input type="hidden" name="a" value="btn_manage" />
    <input type="submit" value="Manage on SynergyCP" class="btn btn-info" />
</form>
