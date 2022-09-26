function CompareEveryItemArray(array1, array2) {

    if (array1.length === array2.length) {
      return array1.every(element => {
        if (array2.includes(element)) {
          return true;
        }
        return false;
      });
    }

    return false;
  }



export default CompareEveryItemArray;
