export default function fieldset(ctx) {
  return `<div class="cmp-card mb-40 mt-2">
<div class="card has-bkg-grey shadow-sm p-big">
  ${
    ctx.component.legend
      ? `
  <div ref="header" class="${
    ctx.component.collapsible
      ? "formio-clickable card-header border-0 p-0 mb-lg-30"
      : "card-header border-0 p-0 mb-lg-30"
  }">
  <div class="d-flex">
   ${
     ctx.component.tooltip
       ? `<i ref="tooltip" tabIndex="0" class="${ctx.iconClass(
           "question-sign"
         )} text-muted" data-tooltip="${ctx.component.tooltip}"></i>`
       : ""
   }
                <h2 class="title-xxlarge mb-1"> ${ctx.t(ctx.component.legend, {
                  _userInput: true,
                })}
                 </h2>
          </div>


  </div>`
      : ""
  }
  ${
    !ctx.collapsed
      ? `<div class="card-body p-0" ref="${ctx.nestedKey}">
    ${ctx.children}
  </div>`
      : ""
  }
  </div>
</div>`;
}
