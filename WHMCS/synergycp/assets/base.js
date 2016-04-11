function ApiResponse() {
  this.callback = {
    success: [],
    fail: []
  };

  this.dispatchSuccess = function (response) {
    for (var i in this.callback.success) {
      this.callback.success[i](response.data, response);
    }
  }.bind(this);

  this.dispatchFail = function (response) {
    for (var i in this.callback.fail) {
      this.callback.fail[i](response.data, response);
    }
  }.bind(this);

  this.success = function (callback) {
    this.callback.success.push(callback);

    return this;
  }.bind(this);

  this.then = function (callback) {
    this.callback.success.push(callback);
    this.callback.fail.push(callback);

    return this;
  }.bind(this);

  this.fail = function (callback) {
    this.callback.fail.push(callback);

    return this;
  }.bind(this);
}

function SynergyApi(options) {
  this.globalErrorHandlers = [];
  this.options = options;

  this.call = function (method, url, data) {
    url = this.url(url);

    var apiResponse = new ApiResponse();

    for (var i in this.globalErrorHandlers) {
      apiResponse.fail(this.globalErrorHandlers[i]);
    }

    data = data || {};
    data.key = this.key();

    $.ajax({
      url: url,
      type: method,
      data: data,
      success: function (response) {
        apiResponse.dispatchSuccess(response);
      },
      fail: function (response) {
        apiResponse.dispatchFail(response);
      }
    });

    return apiResponse;
  }.bind(this);

  this.url = function (path) {
    return this.options.url.trim('/') + '/' + path;
  }.bind(this);

  this.key = function () {
    return this.options.key;
  }.bind(this);

  this.onError = function (callback) {
    this.globalErrorHandlers.push(callback);

    return this;
  }.bind(this);
}

var SCP = {
  init: function (options) {
    this.options = options;

    this.API = new SynergyApi({
      url: this.options.url,
      key: this.options.key
    }).onError(function (data) {
      if (window.console) console.error(data);
    });
  }
};
