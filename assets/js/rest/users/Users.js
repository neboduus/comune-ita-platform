import BasePath from "../../utils/BasePath";
import Auth from "../auth/Auth";
import jwt_decode from "jwt-decode";

class Users {

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

  getFilteredOperators(userGroupId, username) {
    let self = this;
    let url = self.basePath + '/api/users?roles=operator';
    if (userGroupId) {
      url = url + '&user_group_id=' + userGroupId;
    }
    if (username) {
      url = url + '&username=' + username;
    }
    return $.ajax(url,
      {
        method: "GET",
        dataType: 'json', // type of response data
        headers: {
          "Authorization": "Bearer " + self.token
        }
      }
    );
  }

  getUserGroups() {
    let self = this;
    let url = self.basePath + '/api/user-groups';

    return $.ajax(url,
      {
        method: "GET",
        dataType: 'json', // type of response data
      }
    );
  }

  decodeJWTUser(token) {
    return jwt_decode(token);
  }
}

export default Users;
