import BasePath from "../../utils/BasePath";
import Auth from "../auth/Auth";

class Users {

  constructor() {
    this.token = null;
    this.basePath = null;
    this.init()
  }

  init() {
    const auth = new Auth();
    this.token = auth.getToken();
    this.basePath = new BasePath().getBasePath()
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
}

export default Users;
