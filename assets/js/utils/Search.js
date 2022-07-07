import '../services/api.service';

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
          console.log(errorMessage);
        }
      }
    );
  }

  static popolateFilters(data) {
    $.each( data, ( key, value ) => {
      $.each(value, (k, v) => {
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
    if ($('#globalSearchModal').length > 0) {
      this.els = {
        $globalSearchBtn: $('#globalSearchBtn'),
        $globalSearchModal: $('#globalSearchModal')
      }
      this.els.$searchForm = this.els.$globalSearchModal.find('form');


      this.els.$globalSearchBtn.on('click', (e) => {
        if (!this.els.loaded) {
          this.initSearchModal();
          this.els.loaded = true;
        }
        this.els.$globalSearchModal.modal('toggle');
      });

    }
  }

}

$(() => {
    Search.init();
  }
);
