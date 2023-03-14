export default function fieldset(ctx) {
  return `
<section class="it-page-section" id="${ctx.key}">
<div class="cmp-card mb-40 mt-2">
<div class="card has-bkg-grey shadow-sm p-big">
  ${
    ctx.component.legend
      ? `
  <div ref="header" class="${
        ctx.component.collapsible
          ? "formio-clickable card-header border-0 p-0 mb-lg-30"
          : "card-header border-0 p-0 mb-lg-30"
      }">
  <div class="d-flex"><h2 class="title-xxlarge mb-1"> ${ctx.t(
        ctx.component.legend,
        {
          _userInput: true,
        }
      )}
                 </h2>
          </div>
  ${
        ctx.component.tooltip
          ? `
<p class="subtitle-small mb-0">${ctx.t(ctx.component.tooltip)}</p>`
          : ""
      }

  </div>`
      : ""
  }
  ${
    !ctx.collapsed
      ? `<div class="card p-3 p-lg-4 fieldset-body" ref="${ctx.nestedKey}">
                ${ctx.children}

        </div>`
      : ""
  }
  </div>
</div>
</section>`;
}
