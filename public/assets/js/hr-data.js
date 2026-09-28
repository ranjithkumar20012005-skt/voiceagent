/* Zentic HRM - demo data layer (localStorage-backed, client-side only) */
(function (window) {
  "use strict";

  var LS = {
    departments: "zenticHrDepartments",
    employees: "zenticHrEmployees",
    attendance: "zenticHrAttendance",
    leaves: "zenticHrLeaves",
    payroll: "zenticHrPayroll",
    seeded: "zenticHrSeeded"
  };

  function uid(prefix) {
    return prefix + "-" + Date.now().toString(36).slice(-5) + Math.floor(Math.random() * 900 + 100);
  }

  function esc(str) {
    if (str === null || str === undefined) return "";
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function fmtCurrency(n) {
    n = Number(n) || 0;
    return "$" + n.toLocaleString("en-US", { minimumFractionDigits: 0, maximumFractionDigits: 0 });
  }

  function fmtDate(d) {
    var date = (d instanceof Date) ? d : new Date(d);
    if (isNaN(date.getTime())) return d;
    return date.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" });
  }

  function read(key) {
    try {
      var raw = window.localStorage.getItem(key);
      return raw ? JSON.parse(raw) : null;
    } catch (e) { return null; }
  }

  function write(key, value) {
    window.localStorage.setItem(key, JSON.stringify(value));
  }

  function offsetDate(base, days) {
    var d = new Date(base);
    d.setDate(d.getDate() + days);
    return d.toISOString().slice(0, 10);
  }

  var TODAY = "2026-07-01";

  /* ---------- Seed data ---------- */

  function seedDepartments() {
    return [
      { id: "dept-1001", name: "Engineering", head: "Ravi Shah", budget: 850000, description: "Product engineering, platform and QA.", color: "#5046E5" },
      { id: "dept-1002", name: "Sales", head: "Lucia Fernandez", budget: 420000, description: "New business and account management.", color: "#06B6D4" },
      { id: "dept-1003", name: "Marketing", head: "Marcus Webb", budget: 310000, description: "Brand, content and demand generation.", color: "#10B981" },
      { id: "dept-1004", name: "Human Resources", head: "Fatima Al-Sayed", budget: 180000, description: "Talent, culture and people operations.", color: "#F59E0B" },
      { id: "dept-1005", name: "Finance", head: "Chloe Anderson", budget: 260000, description: "Accounting, payroll and financial planning.", color: "#EF4444" }
    ];
  }

  function seedEmployees() {
    return [
      { id: "emp-2001", name: "Ravi Shah", email: "ravi.shah@zentic.com", phone: "+1 (415) 555-0401", departmentId: "dept-1001", designation: "Engineering Manager", employmentType: "Full-time", joinDate: "2021-06-01", salary: 145000, status: "Active", manager: "", avatarImg: "assets/img/avatar/avatar-1.png" },
      { id: "emp-2002", name: "Aisha Malik", email: "aisha.malik@zentic.com", phone: "+1 (415) 555-0402", departmentId: "dept-1001", designation: "Senior Software Engineer", employmentType: "Full-time", joinDate: "2023-02-10", salary: 118000, status: "Active", manager: "Ravi Shah", avatarImg: "assets/img/avatar/avatar-2.png" },
      { id: "emp-2003", name: "Ethan Brooks", email: "ethan.brooks@zentic.com", phone: "+1 (415) 555-0403", departmentId: "dept-1001", designation: "Backend Developer", employmentType: "Full-time", joinDate: "2024-01-15", salary: 98000, status: "Active", manager: "Ravi Shah", avatarImg: "assets/img/avatar/avatar-5.png" },
      { id: "emp-2004", name: "Samuel Okoro", email: "samuel.okoro@zentic.com", phone: "+1 (415) 555-0404", departmentId: "dept-1001", designation: "QA Engineer", employmentType: "Full-time", joinDate: "2022-11-30", salary: 88000, status: "Terminated", manager: "Ravi Shah", avatarImg: "assets/img/avatar/avatar-8.png" },
      { id: "emp-2005", name: "Lucia Fernandez", email: "lucia.fernandez@zentic.com", phone: "+1 (312) 555-0405", departmentId: "dept-1002", designation: "Sales Director", employmentType: "Full-time", joinDate: "2020-09-01", salary: 135000, status: "Active", manager: "", avatarImg: "assets/img/avatar/avatar-3.png" },
      { id: "emp-2006", name: "Omar Siddiqui", email: "omar.siddiqui@zentic.com", phone: "+1 (312) 555-0406", departmentId: "dept-1002", designation: "Account Executive", employmentType: "Full-time", joinDate: "2023-05-20", salary: 78000, status: "Active", manager: "Lucia Fernandez", avatarImg: "assets/img/avatar/avatar-4.png" },
      { id: "emp-2007", name: "Grace Liu", email: "grace.liu@zentic.com", phone: "+1 (312) 555-0407", departmentId: "dept-1002", designation: "Sales Development Rep", employmentType: "Full-time", joinDate: "2025-03-10", salary: 62000, status: "On Leave", manager: "Lucia Fernandez", avatarImg: "assets/img/avatar/avatar-7.png" },
      { id: "emp-2008", name: "Marcus Webb", email: "marcus.webb@zentic.com", phone: "+1 (310) 555-0408", departmentId: "dept-1003", designation: "Marketing Manager", employmentType: "Full-time", joinDate: "2022-04-12", salary: 105000, status: "Active", manager: "", avatarImg: "assets/img/avatar/avatar-1.png" },
      { id: "emp-2009", name: "Isabella Rossi", email: "isabella.rossi@zentic.com", phone: "+1 (310) 555-0409", departmentId: "dept-1003", designation: "Content Strategist", employmentType: "Full-time", joinDate: "2023-11-01", salary: 72000, status: "Active", manager: "Marcus Webb", avatarImg: "assets/img/avatar/avatar-9.png" },
      { id: "emp-2010", name: "Noah Kim", email: "noah.kim@zentic.com", phone: "+1 (310) 555-0410", departmentId: "dept-1003", designation: "SEO Specialist", employmentType: "Contract", joinDate: "2024-07-01", salary: 65000, status: "Active", manager: "Marcus Webb", avatarImg: "assets/img/avatar/avatar-2.png" },
      { id: "emp-2011", name: "Fatima Al-Sayed", email: "fatima.alsayed@zentic.com", phone: "+1 (202) 555-0411", departmentId: "dept-1004", designation: "HR Director", employmentType: "Full-time", joinDate: "2019-08-15", salary: 128000, status: "Active", manager: "", avatarImg: "assets/img/avatar/avatar-5.png" },
      { id: "emp-2012", name: "Derek Johnson", email: "derek.johnson@zentic.com", phone: "+1 (202) 555-0412", departmentId: "dept-1004", designation: "HR Generalist", employmentType: "Full-time", joinDate: "2023-09-05", salary: 68000, status: "Active", manager: "Fatima Al-Sayed", avatarImg: "assets/img/avatar/avatar-6.png" },
      { id: "emp-2013", name: "Chloe Anderson", email: "chloe.anderson@zentic.com", phone: "+1 (646) 555-0413", departmentId: "dept-1005", designation: "Finance Manager", employmentType: "Full-time", joinDate: "2021-01-20", salary: 118000, status: "Active", manager: "", avatarImg: "assets/img/avatar/avatar-3.png" },
      { id: "emp-2014", name: "Victor Chen", email: "victor.chen@zentic.com", phone: "+1 (646) 555-0414", departmentId: "dept-1005", designation: "Financial Analyst", employmentType: "Full-time", joinDate: "2024-02-15", salary: 82000, status: "Active", manager: "Chloe Anderson", avatarImg: "assets/img/avatar/avatar-4.png" }
    ];
  }

  function seedAttendance() {
    var employees = seedEmployees().filter(function (e) { return e.status !== "Terminated"; });
    var pattern = ["Present", "Present", "Present", "Late", "Present", "Absent", "Half Day"];
    var records = [];
    for (var d = 6; d >= 0; d--) {
      var date = offsetDate(TODAY, -d);
      employees.forEach(function (emp, i) {
        if (emp.status === "On Leave" && d < 3) {
          records.push({ id: uid("att"), employeeId: emp.id, date: date, status: "On Leave", checkIn: "", checkOut: "" });
          return;
        }
        var status = pattern[(i + d) % pattern.length];
        var checkIn = status === "Absent" ? "" : (status === "Late" ? "09:47 AM" : "09:02 AM");
        var checkOut = status === "Absent" ? "" : (status === "Half Day" ? "01:15 PM" : "06:05 PM");
        records.push({ id: uid("att"), employeeId: emp.id, date: date, status: status, checkIn: checkIn, checkOut: checkOut });
      });
    }
    return records;
  }

  function seedLeaves() {
    return [
      { id: "leave-3001", employeeId: "emp-2007", type: "Sick Leave", startDate: "2026-06-28", endDate: "2026-07-03", days: 6, reason: "Recovering from minor surgery.", status: "Approved", appliedDate: "2026-06-25" },
      { id: "leave-3002", employeeId: "emp-2003", type: "Vacation", startDate: "2026-07-10", endDate: "2026-07-17", days: 8, reason: "Family trip planned months in advance.", status: "Pending", appliedDate: "2026-06-20" },
      { id: "leave-3003", employeeId: "emp-2009", type: "Personal", startDate: "2026-07-05", endDate: "2026-07-05", days: 1, reason: "Personal appointment.", status: "Approved", appliedDate: "2026-06-29" },
      { id: "leave-3004", employeeId: "emp-2006", type: "Vacation", startDate: "2026-08-01", endDate: "2026-08-10", days: 10, reason: "Annual leave.", status: "Pending", appliedDate: "2026-06-30" },
      { id: "leave-3005", employeeId: "emp-2012", type: "Unpaid Leave", startDate: "2026-06-15", endDate: "2026-06-16", days: 2, reason: "Family emergency.", status: "Rejected", appliedDate: "2026-06-10" },
      { id: "leave-3006", employeeId: "emp-2014", type: "Sick Leave", startDate: "2026-06-22", endDate: "2026-06-23", days: 2, reason: "Flu.", status: "Approved", appliedDate: "2026-06-21" },
      { id: "leave-3007", employeeId: "emp-2002", type: "Personal", startDate: "2026-07-08", endDate: "2026-07-08", days: 1, reason: "Moving apartments.", status: "Pending", appliedDate: "2026-06-27" },
      { id: "leave-3008", employeeId: "emp-2010", type: "Vacation", startDate: "2026-06-05", endDate: "2026-06-09", days: 5, reason: "Pre-planned travel.", status: "Approved", appliedDate: "2026-05-20" }
    ];
  }

  function seedPayroll() {
    var employees = seedEmployees().filter(function (e) { return e.status !== "Terminated"; });
    return employees.map(function (e, i) {
      var bonus = i % 4 === 0 ? Math.round(e.salary * 0.02) : 0;
      var deductions = Math.round(e.salary / 12 * 0.18);
      var basic = Math.round(e.salary / 12);
      var netPay = basic + bonus - deductions;
      return {
        id: uid("pay"),
        employeeId: e.id,
        month: "June 2026",
        basicSalary: basic,
        bonus: bonus,
        deductions: deductions,
        netPay: netPay,
        status: i % 5 === 0 ? "Pending" : "Paid",
        paymentDate: i % 5 === 0 ? "" : "2026-06-30"
      };
    });
  }

  /* ---------- Init / seeding ---------- */

  function init() {
    if (read(LS.seeded)) return;
    write(LS.departments, seedDepartments());
    write(LS.employees, seedEmployees());
    write(LS.attendance, seedAttendance());
    write(LS.leaves, seedLeaves());
    write(LS.payroll, seedPayroll());
    write(LS.seeded, true);
  }

  /* ---------- Public CRUD API ---------- */

  var HrStore = {
    uid: uid,
    escapeHtml: esc,
    formatCurrency: fmtCurrency,
    formatDate: fmtDate,
    today: TODAY,

    resetDemoData: function () {
      window.localStorage.removeItem(LS.seeded);
      init();
    },

    getDepartments: function () { return read(LS.departments) || []; },
    getDepartment: function (id) { return this.getDepartments().filter(function (d) { return d.id === id; })[0] || null; },
    saveDepartment: function (dept) {
      var list = this.getDepartments();
      if (!dept.id) {
        dept.id = uid("dept");
        list.push(dept);
      } else {
        list = list.map(function (d) { return d.id === dept.id ? Object.assign({}, d, dept) : d; });
      }
      write(LS.departments, list);
      return dept;
    },
    deleteDepartment: function (id) {
      write(LS.departments, this.getDepartments().filter(function (d) { return d.id !== id; }));
    },
    departmentEmployeeCount: function (deptId) {
      return this.getEmployees().filter(function (e) { return e.departmentId === deptId && e.status !== "Terminated"; }).length;
    },

    getEmployees: function () { return read(LS.employees) || []; },
    getEmployee: function (id) { return this.getEmployees().filter(function (e) { return e.id === id; })[0] || null; },
    saveEmployee: function (employee) {
      var list = this.getEmployees();
      if (!employee.id) {
        employee.id = uid("emp");
        list.push(employee);
      } else {
        list = list.map(function (e) { return e.id === employee.id ? Object.assign({}, e, employee) : e; });
      }
      write(LS.employees, list);
      return employee;
    },
    deleteEmployee: function (id) {
      write(LS.employees, this.getEmployees().filter(function (e) { return e.id !== id; }));
    },

    getAttendance: function () { return read(LS.attendance) || []; },
    getAttendanceForDate: function (date) {
      return this.getAttendance().filter(function (a) { return a.date === date; });
    },
    getAttendanceForEmployee: function (employeeId) {
      return this.getAttendance().filter(function (a) { return a.employeeId === employeeId; }).sort(function (a, b) { return new Date(b.date) - new Date(a.date); });
    },
    markAttendance: function (employeeId, date, status, checkIn, checkOut) {
      var list = this.getAttendance();
      var idx = list.findIndex(function (a) { return a.employeeId === employeeId && a.date === date; });
      var record = { id: idx > -1 ? list[idx].id : uid("att"), employeeId: employeeId, date: date, status: status, checkIn: checkIn || "", checkOut: checkOut || "" };
      if (idx > -1) list[idx] = record; else list.push(record);
      write(LS.attendance, list);
      return record;
    },

    getLeaves: function () { return read(LS.leaves) || []; },
    getLeave: function (id) { return this.getLeaves().filter(function (l) { return l.id === id; })[0] || null; },
    saveLeave: function (leave) {
      var list = this.getLeaves();
      if (!leave.id) {
        leave.id = uid("leave");
        leave.status = leave.status || "Pending";
        leave.appliedDate = new Date().toISOString().slice(0, 10);
        list.push(leave);
      } else {
        list = list.map(function (l) { return l.id === leave.id ? Object.assign({}, l, leave) : l; });
      }
      write(LS.leaves, list);
      return leave;
    },
    updateLeaveStatus: function (id, status) {
      write(LS.leaves, this.getLeaves().map(function (l) { return l.id === id ? Object.assign({}, l, { status: status }) : l; }));
    },
    deleteLeave: function (id) {
      write(LS.leaves, this.getLeaves().filter(function (l) { return l.id !== id; }));
    },

    getPayroll: function () { return read(LS.payroll) || []; },
    getPayrollForEmployee: function (employeeId) {
      return this.getPayroll().filter(function (p) { return p.employeeId === employeeId; });
    },
    updatePayrollStatus: function (id, status) {
      write(LS.payroll, this.getPayroll().map(function (p) { return p.id === id ? Object.assign({}, p, { status: status, paymentDate: status === "Paid" ? new Date().toISOString().slice(0, 10) : "" }) : p; }));
    }
  };

  init();
  window.HrStore = HrStore;

})(window);
