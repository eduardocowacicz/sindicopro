const sidebar = document.querySelector(".sidebar");
const sidebarToggle = document.querySelector(".sidebar-toggle");
const notification = document.querySelector(".notification");

if (sidebar && sidebarToggle) {
  sidebarToggle.addEventListener("click", () => {
    sidebar.classList.toggle("open");
  });
}

document.querySelectorAll(".js-demo").forEach((button) => {
  button.addEventListener("click", () => {
    if (!notification) return;
    notification.textContent = button.dataset.message || "Ação simulada no mockup.";
    notification.classList.add("show");
    clearTimeout(window.mockup3Timer);
    window.mockup3Timer = setTimeout(() => {
      notification.classList.remove("show");
    }, 1800);
  });
});
