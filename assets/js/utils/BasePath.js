class BasePath {

  getBasePath(){
    const explodedPath = window.location.pathname.split("/");
    return location.origin + '/' + explodedPath[1];
  }

}

export default BasePath;
