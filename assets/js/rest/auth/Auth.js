import BasePath from "../../utils/BasePath";

class Auth {

  constructor() {
    this.token = null;
    this.basePath = null;
    this.init()
  }

  init() {
    this.token = null;
    this.basePath = new BasePath().getBasePath()
    console.log(this.basePath)
  }

  getSessionAuthToken() {
    let self = this;
    $.ajax( self.basePath + '/api/session-auth',
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

  getToken(){
    return this.token
  }
}

export default Auth;
