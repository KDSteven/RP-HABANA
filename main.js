let menu = document.querySelector('#menu-icon');
let navbar = document.querySelector('.navbar');

menu.onclick = () => {
    menu.classList.toggle('bx-x');
    navbar.classList.toggle('activate');
}

window.onscroll = () => {
    menu.classList.toggle('bx-x');
    navbar.classList.toggle('activate');
}

const sr = ScrollReveal ({
    distance: '60px',
    duration: 2500,
    delay: 400,
    reset: true
})

sr.reveal('.text',{delay: 200, origin: 'top'})
sr.reveal('.home',{delay: 50, origin: 'bottom'})
sr.reveal('.info-container',{delay: 800, origin: 'left'})
sr.reveal('.heading',{delay: 300, origin: 'top'})
sr.reveal('.about-header',{delay: 300, origin: 'top'})
sr.reveal('.about-text',{delay: 50, origin: 'bottom'})
sr.reveal('.about-container',{delay: 50, origin: 'top'})