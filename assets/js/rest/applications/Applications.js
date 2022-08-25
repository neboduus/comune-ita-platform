import BasePath from "../../utils/BasePath";
import Auth from "../auth/Auth";

class Applications {

  constructor() {
    this.token = null;
    this.basePath = null;
    this.init()
  }

  init() {
    const auth = new Auth();
    auth.getSessionAuthTokenPromise().then( res => {
      this.token = res.token
    });
    this.basePath = new BasePath().getBasePath()
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
}

export default Applications;
