document.addEventListener("DOMContentLoaded", function () {
    const triggers = document.querySelectorAll(".accordion-trigger");

    triggers.forEach(trigger => {
        trigger.addEventListener("click", function () {
            const isExpanded = this.getAttribute("aria-expanded") === "true";
            const panel = this.nextElementSibling;

            // Toggle current target state attributes
            this.setAttribute("aria-expanded", !isExpanded);

            if (!isExpanded) {
                panel.style.maxHeight = panel.scrollHeight + "px";
                panel.style.opacity = "1";
            } else {
                panel.style.maxHeight = null;
                panel.style.opacity = "0";
            }
        });
    });
});
