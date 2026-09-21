export const canManage = (user) =>
  ["main_admin", "district_admin", "divisional_admin"].includes(user?.role);
export const roleName = (role) =>
  ({
    main_admin: "Super Admin",
    district_admin: "District Admin",
    divisional_admin: "Divisional Admin",
    ministry_head: "Ministry Head",
    officer: "Officer",
  })[role] || role;
