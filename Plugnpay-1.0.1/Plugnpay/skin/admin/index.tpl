<?php
/**
 * CubeCart v6
 * ========================================
 * CubeCart is a registered trade mark of CubeCart Limited
 * Copyright CubeCart Limited 2014. All rights reserved.
 * UK Private Limited Company No. 5323904
 * ========================================
 * Web:   http://www.cubecart.com
 * Email:  sales@devellion.com
 * License:  GPL-3.0 http://opensource.org/licenses/GPL-3.0
 */
?>
<form action="{$VAL_SELF}" method="post" enctype="multipart/form-data">
  <div id="Plugnpay" class="tab_content">
      <h3>{$TITLE}</h3>
      <fieldset><legend>{$LANG.module.cubecart_settings}</legend>
      <div><label for="status">{$LANG.common.status}</label><span><input type="hidden" name="module[status]" id="status" class="toggle" value="{$MODULE.status}" /></span></div>
      <div><label for="position">{$LANG.module.position}</label><span><input type="text" name="module[position]" id="position" class="textbox number" value="{$MODULE.position}" /></span></div>
      <div>
        <label for="scope">{$LANG.module.scope}</label>
        <span>
          <select name="module[scope]">
            <option value="both" {$SELECT_scope_both}>{$LANG.module.both}</option>
            <option value="main" {$SELECT_scope_main}>{$LANG.module.main}</option>
            <option value="mobile" {$SELECT_scope_mobile}>{$LANG.module.mobile}</option>
          </select>
        </span>
      </div>
      <div><label for="default">{$LANG.common.default}</label><span><input type="hidden" name="module[default]" id="default" class="toggle" value="{$MODULE.default}" /></span></div>
      <div><label for="description">{$LANG.common.description} *</label><span><input name="module[desc]" id="description" class="textbox" type="text" value="{$MODULE.desc}" /></span></div>
      <div><label for="acNo">{$LANG.plugnpay.publisher_name}</label><span><input name="module[acNo]" id="acNo" class="textbox" type="text" value="{$MODULE.acNo}" /></span></div>
      <div>
        <label for="mode">{$LANG.plugnpay.mode}</label>
          <span>
            <select name="module[mode]">
              <option value="ss" {$SELECT_mode_ss}>{$LANG.plugnpay.ss}</option>
              <option value="api" {$SELECT_mode_api}>{$LANG.plugnpay.api}</option>
            </select>
          </span>
        </div>
        <div>
        <label for="mode">{$LANG.plugnpay.payment_type}</label>
          <span>
            <select name="module[payment_type]">
              <option value="AUTH_CAPTURE" {$SELECT_payment_type_AUTH_CAPTURE}>{$LANG.plugnpay.auth_capture}</option>
              <option value="AUTH_ONLY" {$SELECT_payment_type_AUTH_ONLY}>{$LANG.plugnpay.auth_only}</option>
            </select>
            </span>
        </div>
      <div><strong>{$LANG.plugnpay.info_trans_pass}</strong></div>
      <div><label for="txnkey">{$LANG.plugnpay.publisher_password}</label><span><input name="module[txnkey]" id="txnkey" class="textbox" type="text" value="{$MODULE.txnkey}" /></span></div>
      <!--<div><label for="password">{$LANG.account.password}</label><span><input name="module[password]" id="password" class="textbox" type="password" value="{$MODULE.password}" autocomplete="off" /></span></div>-->
      </fieldset>
      <fieldset><legend>{$LANG.plugnpay.settings}</legend>
        <p>{$LANG.module.3rd_party_settings_desc}</p>
        <div><label for="password_mode">{$LANG.plugnpay.password_mode}</label><span><input name="password_mode" id="password_mode" class="textbox" type="text" value="{$LANG.plugnpay.password_mode_value}" readonly="readonly" /></span></div>
      </fieldset>
      <p>{$LANG.module.description_options}</p>
    </div>
    {$MODULE_ZONES}
    <div class="form_control">
    <input type="submit" name="save" value="{$LANG.common.save}" />
    </div>
    
    <input type="hidden" name="token" value="{$SESSION_TOKEN}" />
</form>
