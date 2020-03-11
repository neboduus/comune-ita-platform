import Base from 'formiojs/components/_classes/component/Component';
import editForm from './PageBreak/PageBreak.form'
import moment from 'moment'

export default class FormioPageBreak extends Base {
  constructor(component, options, data) {
    super(component, options, data);
  }

  static schema() {
    return Base.schema({
      type: 'pagebreak'
    });
  }

  static builderInfo = {
    title: 'PageBreak',
    group: 'basic',
    icon: 'fa fa-calendar',
    weight: 70,
    schema: FormioPageBreak.schema()
  }

  static editForm = editForm

  /**
   * Render returns an html string of the fully rendered component.
   *
   * @param children - If this class is extendended, the sub string is passed as children.
   * @returns {string}
   */
  render(children) {
    // Calling super.render will wrap it html as a component.
    return super.render(`<div class="row page-break-before pagebreak-container"><div class="col-12 text-center pagebreak-component">Page break</div></div>`);
  }

  /**
   * After the html string has been mounted into the dom, the dom element is returned here. Use refs to find specific
   * elements to attach functionality to.
   *
   * @param element
   * @returns {Promise}
   */
  attach(element) {
    // Allow basic component functionality to attach like field logic and tooltips.
    return super.attach(element);
  }

  /**
   * Get the value of the component from the dom elements.
   *
   * @returns {String}
   */
  getValue() {
    return '';
  }

  /**
   * Set the value of the component into the dom elements.
   *
   * @param value
   * @returns {boolean}
   */
  setValue(value) {
    if (!value) {
      return;
    }
    return;
  }
}
