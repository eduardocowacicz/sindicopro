const navToggle = document.querySelector(".nav-toggle");
const moduleNav = document.querySelector(".module-nav");
const toast = document.querySelector(".notification");

if (navToggle && moduleNav) {
  navToggle.addEventListener("click", () => {
    moduleNav.classList.toggle("open");
  });
}

document.querySelectorAll(".js-demo").forEach((button) => {
  button.addEventListener("click", () => {
    if (!toast) return;
    toast.textContent = button.dataset.message || "Interação simulada no mockup.";
    toast.classList.add("show");
    clearTimeout(window.mockup2Timer);
    window.mockup2Timer = setTimeout(() => toast.classList.remove("show"), 1800);
  });
});
