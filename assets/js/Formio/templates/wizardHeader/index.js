import {truncate} from "../../../utils/truncate";

export default function wizardHeader(ctx) {
  const numberComponentsClass =
    ctx.panels.length > 4 ? "d-none" : "d-none d-lg-flex";
  const numberComponentsMobileClass =
    ctx.panels.length > 4 ? "d-flex" : "d-lg-none";

  const options = ctx.panels.map((panel, index) => {
    return `<div class="info-progress-wrapper w-100 px-1 px-xl-3 flex-column justify-content-center page-item  ${numberComponentsClass} ${
      ctx.currentPage === index ? "step-active" : ""
    }
            ${
              !ctx.instance.components[index].invalid &&
              ctx.currentPage >= index
                ? "completed cursor-pointer"
                : ""
            }" data-wizard="header-${index}">
     <div class="info-progress-body d-flex justify-content-between align-self-end align-items-end w-100 py-3 ${
       !ctx.instance.components[index].invalid && ctx.currentPage >= index
         ? "completed"
         : ""
     }" data-index="${index}" ref="${ctx.wizardKey}-link">
       <span class="d-block h-100 title-medium text-uppercase" data-toggle="tooltip" title="${ctx.t(
         panel.title,
         { _userInput: true }
       )}"> ${truncate(ctx.t(panel.title, { _userInput: true }),30)}</span>
            ${
              !ctx.instance.components[index].invalid &&
              ctx.currentPage >= index
                ? `<svg class="d-block icon icon-primary icon-sm" aria-hidden="true">
                           <use href="/bootstrap-italia/dist/svg/sprite.svg#it-check"></use>
                       </svg>`
                : ""
            }
    </div></div>`;
  });

  options.push(`
            <div class="px-lg-4 iscrizioni-header ${numberComponentsMobileClass} w-100" data-wizard="mobile">
            <!-- Mobile -->
              <h4 class="step-title d-flex align-items-center justify-content-between drop-shadow w-100">
                  <span class="d-block d-lg-inline step-label"></span>
                  <span class="step"></span>
              </h4>
            </div>`);

  let parent = document.getElementById("wizardHeader");
  //Create tmp div for overwrite
  const newDiv = document.createElement("div");
  newDiv.setAttribute("id", "temp");
  newDiv.innerHTML = `${options.join("")}`;

  //Prepend new div in container
  parent.prepend(newDiv);
  document.getElementById("temp").outerHTML =
    document.getElementById("temp").innerHTML;

  // Remove duplicate html
  let found = {};
  $("[data-wizard]").each(function () {
    let $this = $(this);
    if (found[$this.data("wizard")]) {
      $this.remove();
    } else {
      found[$this.data("wizard")] = true;
    }
  });

  // Remove mobile duplicate html
  const tagsMobile = [...document.querySelectorAll(".iscrizioni-header")];
  tagsMobile.length > 1 ? tagsMobile[1].remove() : null;

  // Store steps
  localStorage.setItem("steps", JSON.stringify(options));
}
