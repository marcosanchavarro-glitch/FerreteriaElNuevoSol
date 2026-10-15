const adminMenu = document.querySelector('.admin-sidebar');
if (adminMenu) {
 const desktop = window.matchMedia('(min-width: 901px)');
 const adaptMenu = () => { adminMenu.open = desktop.matches; };
 adaptMenu();
 desktop.addEventListener('change', adaptMenu);
}
