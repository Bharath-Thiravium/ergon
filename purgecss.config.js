module.exports = {
  content: [
    './**/*.php',
    './**/*.html',
    './assets/js/**/*.js'
  ],
  css: ['./assets/css/ergon.css'],
  safelist: {
    standard: [
      "badge","badge--success","badge--warning","badge--danger","badge--info",
      "btn","btn--primary","btn--secondary","btn--danger",
      "card","card__header","card__body","kpi-card",
      "table","table-header__cell","table-header__filter","table-filter-dropdown",
      "main-header","sidebar","nav-dropdown-menu","nav-dropdown-btn",
      "profile-menu","modal","modal-content","ab-btn","ab-container"
    ],
    deep: [/^table-/, /^card-/, /^user-/, /^admin-/, /^kpi-/, /^profile-/, /^ab-/, /^nav-/]
  },
  rejected: true
}