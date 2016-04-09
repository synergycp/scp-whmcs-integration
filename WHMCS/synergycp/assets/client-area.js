function resetHeight () {
  $(this).css('height', 'auto');
}

var refreshPxeStatusInterval = 10*1000;

SCP.ClientArea = {
  class: {
    noIso: 'scp-no-iso'
  },
  options: {},
  operatingSystem: {},
  edition: {},
  installId: null,
  init: function (options) {
    this.options = options;

    this.refreshOperatingSystems();
    this.refreshInstallStatus();
    this.registerListeners();

    setInterval(this.refreshInstallStatus.bind(this), refreshPxeStatusInterval);
  },
  registerListeners: function () {
    this.formElem().submit(function (form) {
      return this.submitOsForm();
    }.bind(this));

    this.osChoiceElem().change(function () {
      this.refreshEditions();
    }.bind(this));

    this.cancelElem().click(function () {
      if (!confirm('Are you sure you wish to cancel this installation?')) {
        return false;
      }
      var serverId = this.options.server_id;
      var installId = this.installId;

      SCP.API.call('DELETE', 'server/'+serverId+'/install/'+installId)
        .success(function () {
          this.setInstall(undefined);
        }.bind(this));

      return false;
    }.bind(this));
  },
  refreshInstallStatus: function () {
    var serverId = this.options.server_id;
    SCP.API.call('GET', 'server/'+serverId+'/install')
      .success(function (data) {
        this.setInstall(data.data.pop());
      }.bind(this));
  },
  setInstall: function (install) {
    var hasInstall = !!install;
    if (!hasInstall) {
      this.statusElem().stop(true, false).slideUp(500, resetHeight);
      this.formElem().stop(true, false).slideDown(500, resetHeight);

      return;
    }

    this.installId = install.id;

    this.statusElem().stop(true, false).slideDown(500, resetHeight);
    this.formElem().stop(true, false).slideUp(500, resetHeight);

    var percent = 100 * parseInt(install.step) / parseInt(install.steps);

    this.setProgress(percent);

    $('#scp-pxe-install-name').text(install.script.name);
    $('#scp-pxe-install-status').text(install.step_desc);
  },
  setProgress: function (percent) {
    this.pxeProgressElem()
      .css('width', percent + '%')
      .text(percent + '%')
      ;
  },
  refreshOperatingSystems: function () {
    SCP.API.call('GET', 'pxe/template')
      .success(function (data) {
        this.setOperatingSystems(data.data);
      }.bind(this));
  },
  refreshEditions: function () {
    var $select = this.editionChoiceElem();
    var val = $select.val();
    $('option', $select).not(':first').remove();

    var osChoice = this.osChoice();
    if (!osChoice) {
      return;
    }

    var isoId = osChoice.iso_id;
    this.formElem().toggleClass(this.class.noIso, !isoId);
    if (!isoId) {
      return;
    }

    SCP.API.call('GET', 'pxe/iso/'+isoId+'/edition')
      .success(function (data) {
        for (var i in data.data) {
          var edition = data.data[i];
          var $option = $('<option></option>');

          this.edition[edition.id] = edition;

          $option.text(edition.name);
          $option.attr('value', edition.id);
          if (val == edition.id) {
            $option.attr('selected', '');
          }

          $select.append($option);
        }
      }.bind(this));
  },
  setOperatingSystems: function (items) {
    var $select = this.osChoiceElem();
    var val = $select.val();
    $('option', $select).not(':first').remove();

    this.operatingSystem = {};

    for (var i in items) {
      var script = items[i];
      var $option = $('<option></option>');

      this.operatingSystem[script.id] = script;

      $option.text(script.name);
      $option.attr('value', script.id);
      if (val == script.id) {
        $option.attr('selected', '');
      }
      $select.append($option);
    }
  },
  submitOsForm: function () {
    var osChoice = this.osChoice();
    var editionChoice = this.editionChoice();

    if (!osChoice) {
      this.error('Please select an operating system');

      return false;
    }

    var serverId = this.options.server_id;
    var data = {
      script_id: osChoice.id,
      edition_id: editionChoice ? editionChoice.id : null,
      license_key: this.licenseKeyElem().val(),
      password: this.passwordElem().val()
    };

    SCP.API.call('POST', 'server/'+serverId+'/install', data)
      .success(function (data) {
        this.success('OS reload started.');
        this.refreshInstallStatus();
      }.bind(this));

    return false;
  },
  error: function (msg) {
    alert(msg);
  },
  success: function (msg) {
    //alert(msg);
  },
  editionChoice: function () {
    var editionId = this.editionChoiceElem().val();

    if (!editionId) {
      return;
    }

    return this.edition[editionId];
  },
  osChoice: function () {
    var osId = this.osChoiceElem().val();

    if (!osId) {
      return;
    }

    return this.operatingSystem[osId];
  },
  pxeProgressElem: function () {
    return $('#scp-pxe-install-progress');
  },
  passwordElem: function () {
    return $('#scp-password');
  },
  licenseKeyElem: function () {
    return $('#scp-license-key');
  },
  osChoiceElem: function () {
    return $('#scp-os-choice');
  },
  editionChoiceElem: function () {
    return $('#scp-edition-choice');
  },
  mainElem: function () {
    return $('#scp-pxe-status');
  },
  formElem: function () {
    return $('#scp-os-reload');
  },
  statusElem: function () {
    return $('#scp-pxe-installing');
  },
  cancelElem: function () {
    return $('#scp-pxe-install-cancel');
  }
};
