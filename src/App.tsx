import React, { useState } from 'react';
import {
  initialSchoolData,
  initialFiscalYears,
  initialUsers,
  initialStudentsData,
  initialRevenuesData,
  initialBudgetAllocations,
  initialLearnerActivities,
  initialProjectsData,
  initialTransactions,
  initialStrategies,
} from './data/initialData';
import {
  School,
  FiscalYear,
  User,
  StudentLevel,
  RevenueItem,
  BudgetAllocation,
  LearnerActivity,
  Project,
  BudgetTransaction,
  Strategy,
} from './types';
import { Header } from './components/Header';
import { Sidebar, ActiveTab } from './components/Sidebar';
import { DashboardView } from './components/DashboardView';
import { SchoolInfoView } from './components/SchoolInfoView';
import { StudentDataView } from './components/StudentDataView';
import { RevenueView } from './components/RevenueView';
import { BudgetAllocationView } from './components/BudgetAllocationView';
import { LearnerActivitiesView } from './components/LearnerActivitiesView';
import { AiProjectWriterView } from './components/AiProjectWriterView';
import { ProjectsView } from './components/ProjectsView';
import { ProjectExpensesView } from './components/ProjectExpensesView';
import { DisbursementsView } from './components/DisbursementsView';
import { ActionPlanView } from './components/ActionPlanView';
import { ReportsView } from './components/ReportsView';
import { SettingsView } from './components/SettingsView';
import { UsersView } from './components/UsersView';
import { SuperAdminView } from './components/SuperAdminView';
import { PhpPackageModal } from './components/PhpPackageModal';
import { Lock, LogIn, Building2 } from 'lucide-react';

export default function App() {
  // App state
  const [school, setSchool] = useState<School>(initialSchoolData);
  const [fiscalYears, setFiscalYears] = useState<FiscalYear[]>(initialFiscalYears);
  const [activeFiscalYear, setActiveFiscalYear] = useState<FiscalYear>(
    initialFiscalYears.find((fy) => fy.isActive) || initialFiscalYears[0]
  );
  const [users, setUsers] = useState<User[]>(initialUsers);
  const [currentUser, setCurrentUser] = useState<User>(initialUsers[0]); // default admin
  const [students, setStudents] = useState<StudentLevel[]>(initialStudentsData);
  const [revenues, setRevenues] = useState<RevenueItem[]>(initialRevenuesData);
  const [allocations, setAllocations] = useState<BudgetAllocation[]>(initialBudgetAllocations);
  const [activities, setActivities] = useState<LearnerActivity[]>(initialLearnerActivities);
  const [projects, setProjects] = useState<Project[]>(initialProjectsData);
  const [transactions, setTransactions] = useState<BudgetTransaction[]>(initialTransactions);
  const [strategies, setStrategies] = useState<Strategy[]>(initialStrategies);

  // Navigation & UI state
  const [activeTab, setActiveTab] = useState<ActiveTab>('dashboard');
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);
  const [isPhpModalOpen, setIsPhpModalOpen] = useState(false);
  const [selectedProjectIdForExpenses, setSelectedProjectIdForExpenses] = useState<number | undefined>(undefined);

  // Auth screen state
  const [isLoggedIn, setIsLoggedIn] = useState(true);
  const [loginUsername, setLoginUsername] = useState('admin');
  const [loginPassword, setLoginPassword] = useState('123456');
  const [loginError, setLoginError] = useState('');

  // Total student count
  const totalStudents = students.reduce((sum, s) => sum + s.totalCount, 0);

  // Total revenue
  const totalRevenue = revenues.reduce((sum, r) => sum + r.calculatedAmount, 0);

  // Count pending projects
  const pendingProjectsCount = projects.filter((p) => !p.approvedBy).length;

  // Handle Login
  const handleLoginSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const found = users.find((u) => u.username === loginUsername.trim());
    if (found && loginPassword === '123456') {
      setCurrentUser(found);
      setIsLoggedIn(true);
      setLoginError('');
    } else {
      setLoginError('ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง (รหัสผ่านทดสอบคือ 123456)');
    }
  };

  // Handle Logout
  const handleLogout = () => {
    setIsLoggedIn(false);
  };

  // Sync revenue amounts when students change
  const handleUpdateStudents = (updatedList: StudentLevel[]) => {
    setStudents(updatedList);
    const newTotal = updatedList.reduce((sum, s) => sum + s.totalCount, 0);

    // Auto-sync eligible count on head-count dependent revenue items
    setRevenues((prev) =>
      prev.map((r) => {
        if (!r.isCustomRate && (r.itemName.includes('นักเรียน') || r.id <= 6 || r.id === 8)) {
          return {
            ...r,
            eligibleCount: newTotal,
            calculatedAmount: Math.round(r.ratePerHead * newTotal),
          };
        }
        return r;
      })
    );
  };

  // Handle preset rate application
  const handleApplyPresetRates = () => {
    setRevenues((prev) =>
      prev.map((r) => {
        if (r.itemName.includes('เงินอุดหนุนรายหัวนักเรียน')) {
          return { ...r, ratePerHead: 2000, calculatedAmount: 2000 * r.eligibleCount };
        }
        if (r.itemName.includes('หนังสือเรียน')) {
          return { ...r, ratePerHead: 650, calculatedAmount: 650 * r.eligibleCount };
        }
        if (r.itemName.includes('เครื่องแบบ')) {
          return { ...r, ratePerHead: 400, calculatedAmount: 400 * r.eligibleCount };
        }
        if (r.itemName.includes('อุปกรณ์การเรียน')) {
          return { ...r, ratePerHead: 220, calculatedAmount: 220 * r.eligibleCount };
        }
        if (r.itemName.includes('กิจกรรมพัฒนาผู้เรียน')) {
          return { ...r, ratePerHead: 500, calculatedAmount: 500 * r.eligibleCount };
        }
        return r;
      })
    );
  };

  // Handle adding a new fiscal year
  const handleAddFiscalYear = (yearNum: number) => {
    const newId = fiscalYears.length + 1;
    const newFy: FiscalYear = {
      id: newId,
      schoolId: 1,
      year: yearNum,
      startDate: `${yearNum - 543 - 1}-10-01`,
      endDate: `${yearNum - 543}-09-30`,
      isActive: true,
      teacherCount: 15,
      isProposalOpen: true,
      proposalOpenDate: `${yearNum - 543 - 1}-10-01`,
      proposalCloseDate: `${yearNum - 543}-01-31`,
      proposalNotice: `เปิดรับการเสนอโครงการตามแผนปฏิบัติการประจำปีงบประมาณ พ.ศ. ${yearNum}`,
    };
    setFiscalYears((prev) => [...prev.map((y) => ({ ...y, isActive: false })), newFy]);
    setActiveFiscalYear(newFy);
  };

  // Handle updating an existing fiscal year (e.g. proposal open/close settings)
  const handleUpdateFiscalYear = (updatedFy: FiscalYear) => {
    setFiscalYears((prev) => prev.map((fy) => (fy.id === updatedFy.id ? updatedFy : fy)));
    setActiveFiscalYear(updatedFy);
  };

  // Handle restoring data from backup JSON
  const handleRestoreData = (backup: any) => {
    if (backup.school) setSchool(backup.school);
    if (backup.activeFiscalYear) setActiveFiscalYear(backup.activeFiscalYear);
    if (backup.students) setStudents(backup.students);
    if (backup.revenues) setRevenues(backup.revenues);
    if (backup.allocations) setAllocations(backup.allocations);
    if (backup.projects) setProjects(backup.projects);
    if (backup.transactions) setTransactions(backup.transactions);
  };

  // Switch to Project Expenses tab for specific project
  const handleOpenExpensesForProject = (project: Project) => {
    setSelectedProjectIdForExpenses(project.id);
    setActiveTab('expenses');
  };

  // Login Screen if logged out
  if (!isLoggedIn) {
    return (
      <div className="min-h-screen bg-slate-900 flex items-center justify-center p-4">
        <div className="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200">
          <div className="bg-gradient-to-tr from-blue-950 via-blue-900 to-indigo-950 p-8 text-center text-white">
            <div className="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-400 text-slate-950 font-black text-2xl shadow-lg mb-3">
              สพ
            </div>
            <h1 className="text-xl font-bold tracking-tight">
              ระบบแผนปฏิบัติการประจำปีและจัดสรรงบประมาณ
            </h1>
            <p className="text-xs text-blue-200 mt-1">
              โรงเรียนระดับการศึกษาขั้นพื้นฐาน (สพฐ.)
            </p>
          </div>

          <form onSubmit={handleLoginSubmit} className="p-6 sm:p-8 space-y-4">
            <div className="text-xs text-slate-500 text-center pb-1">
              เข้าสู่ระบบเพื่อจัดการแผนปฏิบัติการและงบประมาณ
            </div>

            {loginError && (
              <div className="p-3 rounded-lg bg-red-50 border border-red-200 text-xs text-red-700 font-medium">
                {loginError}
              </div>
            )}

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">
                ชื่อผู้ใช้งาน (Username)
              </label>
              <input
                id="login-username-input"
                type="text"
                required
                value={loginUsername}
                onChange={(e) => setLoginUsername(e.target.value)}
                placeholder="admin / director / teacher"
                className="w-full rounded-lg border border-slate-300 p-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-500 focus:outline-none"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">
                รหัสผ่าน (Password)
              </label>
              <input
                id="login-password-input"
                type="password"
                required
                value={loginPassword}
                onChange={(e) => setLoginPassword(e.target.value)}
                placeholder="••••••"
                className="w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
              />
            </div>

            <button
              id="btn-login-submit"
              type="submit"
              className="w-full flex items-center justify-center gap-2 rounded-lg bg-blue-700 hover:bg-blue-800 p-2.5 text-sm font-bold text-white shadow transition-colors"
            >
              <LogIn className="h-4 w-4" />
              <span>เข้าสู่ระบบ</span>
            </button>

            <div className="pt-3 border-t border-slate-100 text-center">
              <div className="text-[11px] text-slate-500">บัญชีทดสอบในระบบ (รหัสผ่าน 123456):</div>
              <div className="flex justify-center gap-2 mt-2">
                <button
                  type="button"
                  onClick={() => {
                    setLoginUsername('admin');
                    setLoginPassword('123456');
                  }}
                  className="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-[11px] font-semibold text-slate-700"
                >
                  admin (แอดมิน)
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setLoginUsername('director');
                    setLoginPassword('123456');
                  }}
                  className="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-[11px] font-semibold text-slate-700"
                >
                  director (ผอ.)
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setLoginUsername('teacher');
                    setLoginPassword('123456');
                  }}
                  className="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-[11px] font-semibold text-slate-700"
                >
                  teacher (ครู)
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-100/70 flex flex-col font-sans text-slate-900">
      {/* Top Header */}
      <Header
        school={school}
        activeFiscalYear={activeFiscalYear}
        currentUser={currentUser}
        onSwitchUser={(user) => setCurrentUser(user)}
        availableUsers={users}
        onOpenPhpModal={() => setIsPhpModalOpen(true)}
        onToggleSidebar={() => setIsSidebarOpen(!isSidebarOpen)}
        onLogout={handleLogout}
        onNavigateToSuperAdmin={() => setActiveTab('super_admin')}
      />

      <div className="flex flex-1 overflow-hidden">
        {/* Sidebar */}
        <Sidebar
          activeTab={activeTab}
          onSelectTab={(tab) => setActiveTab(tab)}
          isOpen={isSidebarOpen}
          onClose={() => setIsSidebarOpen(false)}
          onOpenPhpModal={() => setIsPhpModalOpen(true)}
          onLogout={handleLogout}
          currentUser={currentUser}
          pendingCount={pendingProjectsCount}
        />

        {/* Main Content Area */}
        <main className="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 custom-scrollbar">
          <div className="max-w-7xl mx-auto">
            {school.isActive === false && activeTab !== 'super_admin' && (
              <div className="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-rose-900 shadow-sm">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center text-rose-600 font-bold shrink-0">
                    !
                  </div>
                  <div>
                    <h4 className="text-sm font-bold">สถานศึกษาถูกระงับการใช้งานชั่วคราว (Inactive)</h4>
                    <p className="text-xs text-rose-600">
                      Super Admin ได้ระงับการใช้งานโรงเรียนนี้ เพื่อความปลอดภัยข้อมูลจึงถูกล็อกการบันทึก
                    </p>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={() => setActiveTab('super_admin')}
                  className="px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs transition-colors shrink-0"
                >
                  เปิดหน้า Super Admin เพื่อจัดการ
                </button>
              </div>
            )}

            {activeTab === 'dashboard' && (
              <DashboardView
                school={school}
                activeFiscalYear={activeFiscalYear}
                students={students}
                revenues={revenues}
                allocations={allocations}
                projects={projects}
                transactions={transactions}
                onNavigateTab={(tab) => setActiveTab(tab)}
              />
            )}

            {activeTab === 'school' && (
              <SchoolInfoView
                school={school}
                activeFiscalYear={activeFiscalYear}
                onUpdateSchool={(updated) => setSchool(updated)}
              />
            )}

            {activeTab === 'students' && (
              <StudentDataView
                students={students}
                activeFiscalYear={activeFiscalYear}
                onUpdateStudents={handleUpdateStudents}
              />
            )}

            {activeTab === 'revenue' && (
              <RevenueView
                revenues={revenues}
                activeFiscalYear={activeFiscalYear}
                totalStudents={totalStudents}
                onUpdateRevenues={(updated) => setRevenues(updated)}
              />
            )}

            {activeTab === 'budget' && (
              <BudgetAllocationView
                allocations={allocations}
                activeFiscalYear={activeFiscalYear}
                totalRevenue={totalRevenue}
                onUpdateAllocations={(updated) => setAllocations(updated)}
              />
            )}

            {activeTab === 'learner_activities' && (
              <LearnerActivitiesView
                activities={activities}
                activeFiscalYear={activeFiscalYear}
                revenues={revenues}
                onUpdateActivities={(updated) => setActivities(updated)}
              />
            )}

            {activeTab === 'ai_project_writer' && (
              <AiProjectWriterView
                school={school}
                fiscalYear={activeFiscalYear}
                strategies={strategies}
                onSaveToProjects={(newProject) => {
                  setProjects((prev) => [newProject, ...prev]);
                }}
                onNavigateToProjects={() => setActiveTab('projects')}
              />
            )}

            {activeTab === 'projects' && (
              <ProjectsView
                projects={projects}
                currentUser={currentUser}
                departments={allocations}
                activeFiscalYear={activeFiscalYear}
                onUpdateProjects={(updated) => setProjects(updated)}
                onOpenExpensesForProject={handleOpenExpensesForProject}
                onNavigateToAiWriter={() => setActiveTab('ai_project_writer')}
              />
            )}

            {activeTab === 'expenses' && (
              <ProjectExpensesView
                projects={projects}
                selectedProjectId={selectedProjectIdForExpenses}
                onUpdateProjects={(updated) => setProjects(updated)}
                onBackToProjects={() => setActiveTab('projects')}
              />
            )}

            {activeTab === 'disbursements' && (
              <DisbursementsView
                transactions={transactions}
                projects={projects}
                currentUser={currentUser}
                activeFiscalYear={activeFiscalYear}
                onUpdateTransactions={(updatedTrans, updatedProjects) => {
                  setTransactions(updatedTrans);
                  setProjects(updatedProjects);
                }}
              />
            )}

            {activeTab === 'action_plan' && (
              <ActionPlanView
                projects={projects}
                school={school}
                activeFiscalYear={activeFiscalYear}
              />
            )}

            {activeTab === 'reports' && (
              <ReportsView
                school={school}
                activeFiscalYear={activeFiscalYear}
                students={students}
                revenues={revenues}
                allocations={allocations}
                activities={activities}
                projects={projects}
                transactions={transactions}
              />
            )}

            {activeTab === 'settings' && (
              <SettingsView
                school={school}
                fiscalYears={fiscalYears}
                activeFiscalYear={activeFiscalYear}
                onSelectFiscalYear={(fy) => setActiveFiscalYear(fy)}
                onAddFiscalYear={handleAddFiscalYear}
                onUpdateFiscalYear={handleUpdateFiscalYear}
                students={students}
                revenues={revenues}
                allocations={allocations}
                projects={projects}
                transactions={transactions}
                onRestoreData={handleRestoreData}
                onApplyPresetRates={handleApplyPresetRates}
              />
            )}

            {activeTab === 'users' && (
              <UsersView
                users={users}
                currentUser={currentUser}
                onUpdateUsers={(updated) => setUsers(updated)}
              />
            )}

            {activeTab === 'super_admin' && (
              <SuperAdminView
                currentSchool={school}
                onSelectSchool={(selected) => {
                  setSchool(selected);
                }}
              />
            )}
          </div>
        </main>
      </div>

      {/* cPanel / PHP Installation Package Modal */}
      <PhpPackageModal
        isOpen={isPhpModalOpen}
        onClose={() => setIsPhpModalOpen(false)}
      />
    </div>
  );
}
