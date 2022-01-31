import './Api';

class Search {

  static initSearchModal() {
    $.ajax(
      this.els.$searchForm.data('facets-url'),
      {
        method: "GET",
        dataType: 'json', // type of response data
        success: (data, status, xhr) => {   // success callback function
          this.popolateFilters(data)
        },
        error: (jqXhr, textStatus, errorMessage) => { // error callback
          alert("Si è verificato un errore, si prega di riprovare");
        }
      }
    );
  }

  static popolateFilters(data) {
    $.each( data, ( key, value ) => {
      $.each(value, (k, v) => {
        console.log(`#filter-${key}`);
        this.els.$globalSearchModal
          .find(`#filter-${key} .fields`)
          .append(this.getTplField(key, v))
      })
    });
  }

  static getTplField(section, field) {
    const tpl = `<div class="col-md-4 mb-1">
                    <div class="form-check">
                      <input id="field-${field.id}" type="checkbox" name="${section}[]" value="${field.id}">
                      <label for="field-${field.id}">${field.name}</label>
                    </div>
                  </div>`;
    return tpl;
  }

  static init() {
    this.loaded = false;
    this.els = {
      $globalSearchModal: $('#globalSearchModal')
    }
    this.els.$searchForm = this.els.$globalSearchModal.find('form');
    this.initSearchModal();
  }

}

$(() => {
    Search.init();
  }
);
