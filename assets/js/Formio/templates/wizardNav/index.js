
export default function wizardNav(ctx) {
  const language = document.documentElement.lang.toString();

  const options = ctx.buttonOrder.map(function (button) {

    /*     if (button === 'cancel' && ctx.buttons.cancel) {
          return `
            <button class="btn btn-outline-primary bg-white btn-sm steppers-btn-reset center" ref="${ctx.wizardKey}-cancel" aria-label="${ctx.t('cancelButtonAriaLabel')}">
              <span class="text-button-sm t-primary">${ctx.t('cancel')}</span></button>
          `
           }*/
    if (button === 'previous') {
      return `
        <button class="btn btn-sm steppers-btn-prev p-0 btn-wizard-nav-previous mt-0" ref="${ctx.wizardKey}-previous" aria-label="${ctx.t('previousButtonAriaLabel')}" ${ctx.currentPage === 0 ? 'disabled' : ''}>
        <svg class="icon icon-primary icon-sm" aria-hidden="true"><use href="/bootstrap-italia/dist/svg/sprite.svg#it-chevron-left"></use></svg><span class="text-button-sm t-primary">${Translator.trans('back', {}, 'messages', language)}</span></button>
      `
    }


    if (button === 'next' && ctx.buttons.next) {
      return `
     <button class="btn btn-outline-primary bg-white btn-sm steppers-btn-save saveBtn center mt-0" id="save-draft" type="button">
                <span class="text-button-sm t-primary d-none d-lg-block">${Translator.trans('buttons.save_pratice', {}, 'messages', language)}</span>
                <span class="text-button-sm t-primary d-block d-lg-none">${Translator.trans('salva', {}, 'messages', language)}</span>
              </button>
        <button class="btn btn-primary btn-sm btn-wizard-nav-next steppers-btn-next mt-0" ref="${ctx.wizardKey}-next" aria-label="${ctx.t('nextButtonAriaLabel')}">
   <span class="text-button-sm">${Translator.trans('avanti', {}, 'messages', language)}</span>
              <svg class="icon icon-white icon-sm" aria-hidden="true">
                <use href="/bootstrap-italia/dist/svg/sprite.svg#it-chevron-right"></use>
              </svg></button>
      `
    }
    if (button === 'submit' && ctx.buttons.submit) {
      let disableWizardSubmit = `<button class="btn btn-outline-primary bg-white btn-sm steppers-btn-save saveBtn center mt-0" id="save-draft" type="button">
                <span class="text-button-sm t-primary d-none d-lg-block">${Translator.trans('buttons.save_pratice', {}, 'messages', language)}</span>
                <span class="text-button-sm t-primary d-block d-lg-none">${Translator.trans('salva', {}, 'messages', language)}</span>
              </button>`;
      if (ctx.disableWizardSubmit) {
        disableWizardSubmit += `<button disabled class="btn btn-primary btn-sm steppers-btn-next btn-wizard-nav-submit mt-0" ref="${ctx.wizardKey}-submit" aria-label="${ctx.t('submitButtonAriaLabel')}">${ctx.t('submit')}</button>`
      } else {
        disableWizardSubmit += `<button class="btn btn-primary btn-sm steppers-btn-next btn-wizard-nav-submit mt-0" ref="${ctx.wizardKey}-submit" aria-label="${ctx.t('submitButtonAriaLabel')}">${ctx.t('submit')}</button>`
      }
      return `${disableWizardSubmit}`
    }
  })

  return `<div class="cmp-nav-steps">
<nav class="steppers-nav formio-wizard-nav-container" id="${ctx.wizardKey}-nav">
      ${options.join('')}
  </nav>
  <small class="save-draft-info w-100 mt-1 text-center text-info"><span></span></small></div>`
}


