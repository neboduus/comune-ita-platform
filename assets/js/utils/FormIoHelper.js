import BasePath from './BasePath'
import Auth from "../rest/auth/Auth"
import axios from "axios";

class FormIoHelper {

  static getCurrentLocale() {
    return document.documentElement.lang.toString();
  }

  static getTenantInfo() {
    const basePath = new BasePath().getBasePath();
    axios.get(basePath + '/api/tenants/info').then(resp => {
      return resp.data;
    });
  }

  static fetchDataAsAuthenticatedUser(endPoint) {
    const basePath = new BasePath().getBasePath();
    const auth = new Auth();
    auth.getSessionAuthTokenPromise().then( res => {
      console.log(res)
      axios.get(basePath + '/api/' + endPoint, {
        headers: {
          "Content-Type": "application/json",
          "Authorization": "Bearer " + res.token
        }
      }).then(resp => {
        return resp.data;
      });
    });
  }

  static getRemoteJson(url, headers) {
    let config = {};
    if (headers) {
      config = {
        headers: headers
      }
    }
    axios.get(url, config).then(resp => {
      return resp.data;
    });
  }


}

export default FormIoHelper;
