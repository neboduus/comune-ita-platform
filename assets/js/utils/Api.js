class Api {

  getSessionAuthToken() {
    let self = this;
    $.ajax(self.basePath + '/api/session-auth',
      {
        method: "GET",
        dataType: 'json', // type of response data
        success: function (data, status, xhr) {   // success callback function
          console.log('getSessionAuthToken')
          self.token = data.token;
        },
        error: function (jqXhr, textStatus, errorMessage) { // error callback
          alert("Si è verificato un errore, si prega di riprovare");
        }
      }
    );
  }

  // Users
  getUsers(q) {
    let self = this;
    return $.ajax(self.basePath + '/api/users?cf=' + q,
      {
        method: "GET",
        dataType: 'json', // type of response data
        headers: {
          "Authorization": "Bearer " + self.token
        }
      }
    );
  }

  // Applications
  postApplication(application) {
    let self = this;
    return $.ajax(self.basePath + '/api/applications',
      {
        method: "POST",
        dataType: 'json', // type of response data
        data: application,
        headers: {
          "Content-Type": "application/json",
          "Authorization": "Bearer " + self.token
        }
      }
    )
  }

  getSessionAuthTokenPromise() {
    let self = this;
    return new Promise((resolve, reject) => {
      $.ajax({
        url: self.basePath + '/api/session-auth',
        dataType: 'json',
        type: 'GET',
        success: function (data) {
          resolve(data)
        },
        error: function (error) {
          reject(error)
        }
      })
    })
  }

  init() {
    let explodedPath = window.location.pathname.split("/");
    this.basePath = location.origin + '/' + explodedPath[1];
    this.token = null;  //this.getSessionAuthToken();
  }

  constructor() {
    this.basePath = null;
    this.token = null;
    this.init()
  }
}

export default Api;
