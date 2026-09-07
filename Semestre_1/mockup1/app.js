const sidebar = document.querySelector(".sidebar");
const toggle = document.querySelector(".mobile-toggle");
const notification = document.querySelector(".notification");

if (toggle && sidebar) {
  toggle.addEventListener("click", () => {
    sidebar.classList.toggle("open");
  });
}

document.querySelectorAll(".js-demo").forEach((button) => {
  button.addEventListener("click", () => {
    if (!notification) return;
    notification.textContent = button.dataset.message || "Ação simulada no mockup.";
    notification.classList.add("show");
    window.clearTimeout(window.mockupTimer);
    window.mockupTimer = window.setTimeout(() => {
      notification.classList.remove("show");
    }, 1800);
  });
});
