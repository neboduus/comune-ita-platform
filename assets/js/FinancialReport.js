import Base from 'formiojs/components/_classes/component/Component';
import editForm from './FinancialReport/FinancialReport.form'

export default class FinancialReport extends Base {
  constructor(component, options, data) {
    super(component, options, data);
    this.financialValues = false;
  }

  static schema() {
    return Base.schema({
      type: 'financial_report'
    });
  }

  static builderInfo = {
    title: 'Bilancio',
    group: 'basic',
    icon: 'money',
    weight: 70,
    documentation: 'http://help.form.io/userguide/#table',
    schema: FinancialReport.schema()
  }

  static editForm = editForm;

  /**
   * Render returns an html string of the fully rendered component.
   *
   * @param children - If this class is extendended, the sub string is passed as children.
   * @returns {string}
   */
  render(children) {
    // To make this dynamic, we could call this.renderTemplate('templatename', {}).
    let content = this.renderTemplate('input', {
      input: {
        type: 'input',
        ref: `${this.component.key}`,
        attr: {
          id: `${this.component.key}`,
          class: 'form-control',
          type: 'hidden',
        }
      }
    });

    // Calling super.render will wrap it html as a component.
    return super.render(`<div class="row"><div class="col-12">${this.component.label}${content}</div></div>`);
  }

  /**
   * After the html string has been mounted into the dom, the dom element is returned here. Use refs to find specific
   * elements to attach functionality to.
   *
   * @param element
   * @returns {Promise}
   */
  attach(element) {
    // Default multiple
    this.component.multiple = true;
    if (!this.financialValues && typeof this.component.data != 'undefined') {
      this.financialValues = this.component.data.values;
    }

    this.updateValue();
    return super.attach(element);
  }

  /**
   * Get the value of the component from the dom elements.
   *
   * @returns {Array}
   */
  getValue() {
    return this.financialValues;
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
    this.financialValues = value;
  }
}
