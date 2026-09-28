/* Zentic Project Management - demo data layer (localStorage-backed, client-side only) */
(function (window) {
  "use strict";

  var LS = {
    projects: "zenticPmProjects",
    tasks: "zenticPmTasks",
    seeded: "zenticPmSeeded"
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

  var TASK_STATUS_ORDER = ["To Do", "In Progress", "In Review", "Done"];

  var TEAM_MEMBERS = [
    { name: "Priya Shah", role: "Product Designer", avatarImg: "assets/img/avatar/avatar-1.png" },
    { name: "Daniel Cho", role: "Frontend Developer", avatarImg: "assets/img/avatar/avatar-2.png" },
    { name: "Maria Gomez", role: "Project Manager", avatarImg: "assets/img/avatar/avatar-5.png" },
    { name: "Tom Becker", role: "QA Engineer", avatarImg: "assets/img/avatar/avatar-6.png" },
    { name: "Elena Kim", role: "Backend Developer", avatarImg: "assets/img/avatar/avatar-3.png" },
    { name: "Jordan Blake", role: "DevOps Engineer", avatarImg: "assets/img/avatar/avatar-4.png" }
  ];

  /* ---------- Seed data ---------- */

  function seedProjects() {
    return [
      { id: "pm-1001", name: "Website Redesign", client: "Nova Cloud Systems", description: "Full redesign of the marketing site with a new design system.", status: "In Progress", priority: "Medium", startDate: "2026-05-01", endDate: "2026-07-31", budget: 45000, team: ["Priya Shah", "Daniel Cho"], color: "#5046E5", createdAt: "2026-04-20" },
      { id: "pm-1002", name: "Mobile App Launch", client: "BrightPath Logistics", description: "Native iOS/Android app for fleet tracking and driver check-ins.", status: "In Progress", priority: "High", startDate: "2026-04-15", endDate: "2026-08-15", budget: 120000, team: ["Daniel Cho", "Elena Kim", "Tom Becker"], color: "#06B6D4", createdAt: "2026-04-01" },
      { id: "pm-1003", name: "CRM Integration", client: "Golden Gate Realty", description: "Integrate the sales pipeline with the client's existing CRM.", status: "Planning", priority: "Medium", startDate: "2026-07-01", endDate: "2026-09-30", budget: 60000, team: ["Maria Gomez", "Elena Kim"], color: "#F59E0B", createdAt: "2026-06-15" },
      { id: "pm-1004", name: "Data Migration", client: "Meridian Health Group", description: "Migrate legacy patient records system to the new cloud database.", status: "On Hold", priority: "High", startDate: "2026-03-01", endDate: "2026-06-30", budget: 35000, team: ["Elena Kim", "Jordan Blake"], color: "#EF4444", createdAt: "2026-02-15" },
      { id: "pm-1005", name: "Marketing Campaign Q3", client: "Bluewave Media", description: "End-to-end creative and scheduling for the Q3 brand campaign.", status: "Completed", priority: "Low", startDate: "2026-04-01", endDate: "2026-06-15", budget: 18000, team: ["Priya Shah", "Maria Gomez"], color: "#3B82F6", createdAt: "2026-03-20" },
      { id: "pm-1006", name: "ERP Rollout", client: "Pinnacle Manufacturing Co.", description: "Company-wide ERP implementation covering inventory and finance.", status: "In Progress", priority: "High", startDate: "2026-02-01", endDate: "2026-10-31", budget: 250000, team: ["Maria Gomez", "Jordan Blake", "Tom Becker", "Elena Kim"], color: "#10B981", createdAt: "2026-01-15" },
      { id: "pm-1007", name: "Compliance Audit Tool", client: "Summit Financial Partners", description: "Internal tool for automated regulatory compliance reporting.", status: "In Progress", priority: "High", startDate: "2026-05-15", endDate: "2026-08-01", budget: 90000, team: ["Daniel Cho", "Tom Becker"], color: "#0891B2", createdAt: "2026-05-01" },
      { id: "pm-1008", name: "POS Refresh", client: "Evergreen Retail Group", description: "In-store POS hardware and software refresh across all locations.", status: "Cancelled", priority: "Low", startDate: "2026-01-01", endDate: "2026-03-01", budget: 22000, team: ["Priya Shah"], color: "#F97316", createdAt: "2025-12-10" }
    ];
  }

  function seedTasks() {
    return [
      { id: "task-2001", projectId: "pm-1001", title: "Design homepage wireframes", description: "Low-fidelity wireframes for the new homepage layout.", assignee: "Priya Shah", priority: "Medium", status: "Done", dueDate: "2026-05-20", createdAt: "2026-05-01" },
      { id: "task-2002", projectId: "pm-1001", title: "Implement responsive navbar", description: "Build the new sticky nav with mobile drawer.", assignee: "Daniel Cho", priority: "Medium", status: "In Progress", dueDate: "2026-06-25", createdAt: "2026-05-10" },
      { id: "task-2003", projectId: "pm-1001", title: "Cross-browser QA pass", description: "Verify layout on Chrome, Safari, Firefox and Edge.", assignee: "Daniel Cho", priority: "Low", status: "To Do", dueDate: "2026-07-10", createdAt: "2026-05-15" },

      { id: "task-2004", projectId: "pm-1002", title: "Build onboarding flow", description: "First-run experience with permission prompts.", assignee: "Elena Kim", priority: "High", status: "In Progress", dueDate: "2026-06-10", createdAt: "2026-04-20" },
      { id: "task-2005", projectId: "pm-1002", title: "Push notification integration", description: "Wire up FCM/APNs for delivery status alerts.", assignee: "Daniel Cho", priority: "High", status: "To Do", dueDate: "2026-07-01", createdAt: "2026-04-25" },
      { id: "task-2006", projectId: "pm-1002", title: "Beta testing with 50 users", description: "Recruit and manage the closed beta cohort.", assignee: "Tom Becker", priority: "Urgent", status: "In Review", dueDate: "2026-07-20", createdAt: "2026-05-05" },

      { id: "task-2007", projectId: "pm-1003", title: "Requirements gathering workshop", description: "Two-day workshop with client stakeholders.", assignee: "Maria Gomez", priority: "Medium", status: "Done", dueDate: "2026-07-05", createdAt: "2026-06-15" },
      { id: "task-2008", projectId: "pm-1003", title: "API schema design", description: "Define the contract for the CRM sync service.", assignee: "Elena Kim", priority: "Medium", status: "To Do", dueDate: "2026-07-25", createdAt: "2026-06-20" },
      { id: "task-2009", projectId: "pm-1003", title: "Data mapping spec", description: "Map legacy fields to new CRM schema.", assignee: "Elena Kim", priority: "Low", status: "To Do", dueDate: "2026-08-05", createdAt: "2026-06-22" },

      { id: "task-2010", projectId: "pm-1004", title: "Legacy database audit", description: "Full inventory of legacy tables and dependencies.", assignee: "Elena Kim", priority: "High", status: "Done", dueDate: "2026-03-20", createdAt: "2026-03-01" },
      { id: "task-2011", projectId: "pm-1004", title: "Migration scripts", description: "ETL scripts for patient records migration.", assignee: "Jordan Blake", priority: "High", status: "In Progress", dueDate: "2026-06-15", createdAt: "2026-03-05" },
      { id: "task-2012", projectId: "pm-1004", title: "Rollback plan documentation", description: "Document rollback steps in case of migration failure.", assignee: "Jordan Blake", priority: "Medium", status: "To Do", dueDate: "2026-06-28", createdAt: "2026-03-10" },

      { id: "task-2013", projectId: "pm-1005", title: "Creative asset production", description: "Banner ads, social creatives and video cuts.", assignee: "Priya Shah", priority: "Medium", status: "Done", dueDate: "2026-05-01", createdAt: "2026-04-01" },
      { id: "task-2014", projectId: "pm-1005", title: "Campaign scheduling", description: "Schedule all placements across channels.", assignee: "Maria Gomez", priority: "Low", status: "Done", dueDate: "2026-05-15", createdAt: "2026-04-10" },
      { id: "task-2015", projectId: "pm-1005", title: "Performance report", description: "Final campaign performance summary for the client.", assignee: "Maria Gomez", priority: "Low", status: "Done", dueDate: "2026-06-10", createdAt: "2026-04-15" },

      { id: "task-2016", projectId: "pm-1006", title: "Vendor module configuration", description: "Configure supplier and purchase order workflows.", assignee: "Jordan Blake", priority: "High", status: "In Progress", dueDate: "2026-07-15", createdAt: "2026-02-01" },
      { id: "task-2017", projectId: "pm-1006", title: "Inventory sync testing", description: "Validate real-time inventory sync across warehouses.", assignee: "Tom Becker", priority: "Urgent", status: "In Review", dueDate: "2026-07-05", createdAt: "2026-02-10" },
      { id: "task-2018", projectId: "pm-1006", title: "User training sessions", description: "Onsite training for warehouse and finance staff.", assignee: "Maria Gomez", priority: "Medium", status: "To Do", dueDate: "2026-09-01", createdAt: "2026-02-15" },
      { id: "task-2019", projectId: "pm-1006", title: "Finance module integration", description: "Connect general ledger to the new ERP finance module.", assignee: "Elena Kim", priority: "High", status: "In Progress", dueDate: "2026-08-10", createdAt: "2026-02-20" },

      { id: "task-2020", projectId: "pm-1007", title: "Regulatory requirements review", description: "Review applicable financial compliance regulations.", assignee: "Daniel Cho", priority: "High", status: "Done", dueDate: "2026-06-01", createdAt: "2026-05-15" },
      { id: "task-2021", projectId: "pm-1007", title: "Reporting dashboard build", description: "Build the automated compliance reporting dashboard.", assignee: "Daniel Cho", priority: "High", status: "In Progress", dueDate: "2026-07-20", createdAt: "2026-05-20" },
      { id: "task-2022", projectId: "pm-1007", title: "Security penetration test", description: "Third-party pen test before go-live.", assignee: "Tom Becker", priority: "Urgent", status: "To Do", dueDate: "2026-07-28", createdAt: "2026-05-25" },

      { id: "task-2023", projectId: "pm-1008", title: "Hardware vendor evaluation", description: "Evaluate POS terminal vendors and pricing.", assignee: "Priya Shah", priority: "Low", status: "Done", dueDate: "2026-02-01", createdAt: "2026-01-05" },
      { id: "task-2024", projectId: "pm-1008", title: "Project cancelled - budget freeze", description: "Client paused all capital spending for the fiscal year.", assignee: "Priya Shah", priority: "Low", status: "Done", dueDate: "2026-03-01", createdAt: "2026-01-10" }
    ];
  }

  /* ---------- Init / seeding ---------- */

  function init() {
    if (read(LS.seeded)) return;
    write(LS.projects, seedProjects());
    write(LS.tasks, seedTasks());
    write(LS.seeded, true);
  }

  /* ---------- Public CRUD API ---------- */

  var PmStore = {
    uid: uid,
    escapeHtml: esc,
    formatCurrency: fmtCurrency,
    formatDate: fmtDate,
    taskStatusOrder: TASK_STATUS_ORDER,
    teamMembers: TEAM_MEMBERS,

    resetDemoData: function () {
      window.localStorage.removeItem(LS.seeded);
      init();
    },

    getProjects: function () { return read(LS.projects) || []; },
    getProject: function (id) { return this.getProjects().filter(function (p) { return p.id === id; })[0] || null; },
    saveProject: function (project) {
      var list = this.getProjects();
      if (!project.id) {
        project.id = uid("pm");
        project.createdAt = new Date().toISOString().slice(0, 10);
        list.push(project);
      } else {
        list = list.map(function (p) { return p.id === project.id ? Object.assign({}, p, project) : p; });
      }
      write(LS.projects, list);
      return project;
    },
    deleteProject: function (id) {
      write(LS.projects, this.getProjects().filter(function (p) { return p.id !== id; }));
      write(LS.tasks, this.getTasks().filter(function (t) { return t.projectId !== id; }));
    },

    getTasks: function () { return read(LS.tasks) || []; },
    getTask: function (id) { return this.getTasks().filter(function (t) { return t.id === id; })[0] || null; },
    saveTask: function (task) {
      var list = this.getTasks();
      if (!task.id) {
        task.id = uid("task");
        task.createdAt = new Date().toISOString().slice(0, 10);
        list.push(task);
      } else {
        list = list.map(function (t) { return t.id === task.id ? Object.assign({}, t, task) : t; });
      }
      write(LS.tasks, list);
      return task;
    },
    deleteTask: function (id) {
      write(LS.tasks, this.getTasks().filter(function (t) { return t.id !== id; }));
    },
    updateTaskStatus: function (id, status) {
      write(LS.tasks, this.getTasks().map(function (t) { return t.id === id ? Object.assign({}, t, { status: status }) : t; }));
    },

    projectTasks: function (projectId) {
      return this.getTasks().filter(function (t) { return t.projectId === projectId; });
    },
    projectProgress: function (projectId) {
      var tasks = this.projectTasks(projectId);
      if (!tasks.length) return 0;
      var done = tasks.filter(function (t) { return t.status === "Done"; }).length;
      return Math.round((done / tasks.length) * 100);
    },
    memberWorkload: function (name) {
      return this.getTasks().filter(function (t) { return t.assignee === name && t.status !== "Done"; }).length;
    },
    memberTaskCount: function (name) {
      return this.getTasks().filter(function (t) { return t.assignee === name; }).length;
    }
  };

  init();
  window.PmStore = PmStore;

})(window);
