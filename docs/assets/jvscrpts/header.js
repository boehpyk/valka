new class HeaderScripts {

  constructor() {
    this.headerEl = document.querySelector('#header');
    this.menuIconEl = document.querySelector('#menu-icon');
    this.navEl = document.querySelector('#nav');

    this.onScroll();
    this.menuIconEl.addEventListener('click', this.toggleNav.bind(this));
    this.navEl.querySelectorAll('a').forEach(el => {
      el.addEventListener('click', this.closeNav.bind(this));
    });
    window.addEventListener('scroll', this.onScroll.bind(this));
  }

  onScroll() {
    if (window.scrollY > 150) {
      this.headerEl.classList.add('header_position_not-top');
    }
    else {
      this.headerEl.classList.remove('header_position_not-top');
      this.closeNav();
    }
  }

  toggleNav() {
    const isNavOpened = this.menuIconEl.classList.contains('menu-icon_opened');
    if (isNavOpened) this.closeNav();
    else this.openNav();
  }

  openNav() {
    this.menuIconEl.classList.add('menu-icon_opened');
    this.navEl.classList.add('nav_opened');
  }

  closeNav() {
    this.menuIconEl.classList.remove('menu-icon_opened');
    this.navEl.classList.remove('nav_opened');
  }

};


