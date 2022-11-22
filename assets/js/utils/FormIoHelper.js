import BasePath from './BasePath'
import Auth from "../rest/auth/Auth"
import axios from "axios";

class FormIoHelper {

  constructor() {
    this.token = null;
    this.basePath = null;
    this.init()
  }

  init() {
    const auth = new Auth();
    auth.getSessionAuthTokenPromise().then(res => {
      this.token = res.token
    });
    this.basePath = new BasePath().getBasePath()
  }

  getCurrentLocale() {
    return document.documentElement.lang.toString();
  }

  async getTenantInfo() {
    const response = await axios.get(this.basePath + '/api/tenants/info')
    return response.data
  }

  async authenticatedCall(endPoint) {
    const response = await axios.get(this.basePath + '/api/' + endPoint, {
      headers: {
        "Content-Type": "application/json",
        "Authorization": "Bearer " + this.token
      }
    })
    return response.data
  }

  async getRemoteJson(url, method = 'get', headers = null) {
    let config = {};
    if (headers) {
      config = {
        headers: headers
      }
    }
    const response = await axios({
      method: method,
      url: url,
      params: config
    });
    return response.data
  }


}

export default FormIoHelper;
