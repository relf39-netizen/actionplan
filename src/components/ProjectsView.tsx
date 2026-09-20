import React, { useState } from 'react';
import { Project, User, BudgetAllocation, FiscalYear } from '../types';
import {
  FolderGit2,
  Plus,
  Search,
  Filter,
  CheckCircle2,
  Clock,
  AlertCircle,
  FileSpreadsheet,
  Edit,
  Trash2,
  Check,
  ShieldCheck,
  X,
  Calendar,
  UserCheck,
  Bot,
  Sparkles,
  Lock,
  Unlock,
} from 'lucide-react';

interface ProjectsViewProps {
  projects: Project[];
  currentUser: User;
  departments: BudgetAllocation[];
  activeFiscalYear: FiscalYear;
  onUpdateProjects: (updated: Project[]) => void;
  onOpenExpensesForProject: (project: Project) => void;
  onNavigateToAiWriter?: () => void;
}

function formatCitizenId(id?: string) {
  if (!id) return '';
  const clean = id.replace(/\D/g, '');
  if (clean.length !== 13) return id;
  return `${clean[0]}-${clean.slice(1, 5)}-${clean.slice(5, 10)}-${clean.slice(10, 12)}-${clean[12]}`;
}

export const ProjectsView: React.FC<ProjectsViewProps> = ({
  projects,
  currentUser,
  departments,
  activeFiscalYear,
  onUpdateProjects,
  onOpenExpensesForProject,
  onNavigateToAiWriter,
}) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [deptFilter, setDeptFilter] = useState('all');
  const [statusFilter, setStatusFilter] = useState('all');

  // Modal states
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingProject, setEditingProject] = useState<Project | null>(null);

  // Form State for new/edit
  const [formData, setFormData] = useState<Partial<Project>>({
    projectCode: '',
    projectName: '',
    rationales: '',
    objectives: '',
    quantitativeTarget: '',
    qualitativeTarget: '',
    kpi: '',
    procedures: '',
    duration: `ตลอดปีการศึกษา ${activeFiscalYear.year}`,
    location: 'โรงเรียนเด็กเรียนดี',
    targetGroup: 'นักเรียนและครูทุกคน',
    responsiblePerson: currentUser.fullName,
    proposerName: currentUser.fullName,
    proposerCitizenId: currentUser.citizenId || '',
    attachmentName: '',
    department: departments[0]?.departmentName || 'ฝ่ายวิชาการ',
    budgetSource: 'เงินอุดหนุนรายหัว (สพฐ.)',
    allocatedBudget: 50000,
    status: 'not_started',
    approvedBy: undefined,
    approvedDate: undefined,
  });

  // Filter projects
  const filteredProjects = projects.filter((p) => {
    const matchSearch =
      p.projectName.toLowerCase().includes(searchTerm.toLowerCase()) ||
      p.projectCode.toLowerCase().includes(searchTerm.toLowerCase()) ||
      p.responsiblePerson.toLowerCase().includes(searchTerm.toLowerCase());
    const matchDept = deptFilter === 'all' || p.department === deptFilter;
    const matchStatus = statusFilter === 'all' || p.status === statusFilter;
    return matchSearch && matchDept && matchStatus;
  });

  const handleOpenAddModal = () => {
    if (activeFiscalYear.isProposalOpen === false && currentUser.role !== 'admin') {
      alert(`ขณะนี้ระบบปิดรับการเสนอโครงการประจำปีงบประมาณ พ.ศ. ${activeFiscalYear.year}\n${activeFiscalYear.proposalNotice || 'กรุณาติดต่อฝ่ายแผนงานหรือผู้บริหารสถานศึกษา'}`);
      return;
    }
    setEditingProject(null);
    const codeNum = projects.length + 1;
    setFormData({
      projectCode: `P68-${codeNum < 10 ? '0' + codeNum : codeNum}`,
      projectName: '',
      rationales: 'เพื่อส่งเสริมและพัฒนาการจัดการศึกษาตามมาตรฐานการศึกษาขั้นพื้นฐาน',
      objectives: '1. เพื่อพัฒนาศักยภาพผู้เรียน\n2. เพื่อยกระดับผลสัมฤทธิ์ทางการเรียน',
      quantitativeTarget: 'นักเรียนร้อยละ 85 ได้รับการพัฒนา',
      qualitativeTarget: 'นักเรียนมีทักษะและคุณลักษณะอันพึงประสงค์ตามเกณฑ์',
      kpi: 'ร้อยละของนักเรียนที่ผ่านเกณฑ์ประเมินไม่น้อยกว่า 85%',
      procedures: '1. วางแผนดำเนินงาน (P)\n2. ดำเนินการตามกิจกรรม (D)\n3. นิเทศติดตามประเมินผล (C)\n4. ปรับปรุงพัฒนาและสรุปรายงาน (A)',
      duration: `พฤษภาคม ${activeFiscalYear.year} - มีนาคม ${activeFiscalYear.year + 1}`,
      location: 'โรงเรียนเด็กเรียนดี',
      targetGroup: 'นักเรียนและครูทุกคน',
      responsiblePerson: currentUser.fullName,
      proposerName: currentUser.fullName,
      proposerCitizenId: currentUser.citizenId || '',
      attachmentName: '',
      department: departments[0]?.departmentName || 'ฝ่ายวิชาการ',
      budgetSource: 'เงินอุดหนุนรายหัว (สพฐ.)',
      allocatedBudget: 30000,
      status: 'not_started',
      approvedBy: undefined,
      approvedDate: undefined,
    });
    setIsModalOpen(true);
  };

  const handleOpenEditModal = (p: Project) => {
    setEditingProject(p);
    setFormData({ ...p });
    setIsModalOpen(true);
  };

  const handleDeleteProject = (id: number) => {
    if (confirm('ยืนยันการลบโครงการนี้ออกจากแผนปฏิบัติการประจำปี?')) {
      const updated = projects.filter((p) => p.id !== id);
      onUpdateProjects(updated);
    }
  };

  const handleSaveModal = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.projectName || !formData.projectCode) {
      alert('กรุณากรอกรหัสและชื่อโครงการ');
      return;
    }

    if (formData.proposerCitizenId && formData.proposerCitizenId.replace(/\D/g, '').length !== 13) {
      alert('เลขประจำตัวประชาชนของครูผู้เสนอโครงการต้องมีครบ 13 หลัก');
      return;
    }

    const cleanCitizenId = formData.proposerCitizenId ? formData.proposerCitizenId.replace(/\D/g, '') : undefined;
    const cleanResponsiblePerson = formData.proposerName || formData.responsiblePerson || currentUser.fullName;

    if (editingProject) {
      // Update
      const updated = projects.map((p) => {
        if (p.id === editingProject.id) {
          const alloc = Number(formData.allocatedBudget) || 0;
          return {
            ...p,
            ...(formData as Project),
            responsiblePerson: cleanResponsiblePerson,
            proposerCitizenId: cleanCitizenId,
            proposerName: cleanResponsiblePerson,
            allocatedBudget: alloc,
            remainingBudget: Math.max(0, alloc - p.spentBudget),
          };
        }
        return p;
      });
      onUpdateProjects(updated);
    } else {
      // Create new
      const newId = projects.length > 0 ? Math.max(...projects.map((p) => p.id)) + 1 : 1;
      const alloc = Number(formData.allocatedBudget) || 0;
      const newProj: Project = {
        ...(formData as Project),
        id: newId,
        schoolId: 1,
        fiscalYearId: activeFiscalYear.id,
        responsiblePerson: cleanResponsiblePerson,
        proposerCitizenId: cleanCitizenId,
        proposerName: cleanResponsiblePerson,
        allocatedBudget: alloc,
        spentBudget: 0,
        remainingBudget: alloc,
        status: formData.status || 'not_started',
        approvalStatus: currentUser.role === 'admin' || currentUser.role === 'director' ? 'approved' : 'pending',
      };
      onUpdateProjects([...projects, newProj]);
    }
    setIsModalOpen(false);
  };

  // Director quick approval
  const handleApproveProject = (project: Project) => {
    const updated = projects.map((p) => {
      if (p.id === project.id) {
        return {
          ...p,
          approvedBy: currentUser.fullName,
          approvedDate: new Date().toISOString().split('T')[0],
          status: p.status === 'not_started' ? 'in_progress' : p.status,
        };
      }
      return p;
    });
    onUpdateProjects(updated);
  };

  // Quick Status change
  const handleQuickStatusChange = (project: Project, newStatus: Project['status']) => {
    const updated = projects.map((p) => (p.id === project.id ? { ...p, status: newStatus } : p));
    onUpdateProjects(updated);
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 pb-4">
        <div>
          <h2 className="text-xl font-bold text-slate-900 flex items-center gap-2">
            <FolderGit2 className="h-6 w-6 text-blue-700" />
            <span>ระบบบริหารโครงการตามแผนปฏิบัติการ (Project Management)</span>
          </h2>
          <p className="text-xs text-slate-500 mt-0.5">
            บันทึกรายละเอียด วัตถุประสงค์ ตัวชี้วัด กิจกรรม วงเงินงบประมาณ และสถานะการอนุมัติโครงการ
          </p>
        </div>

        <div className="flex items-center gap-2">
          {onNavigateToAiWriter && (
            <button
              id="btn-nav-ai-writer-shortcut"
              type="button"
              onClick={onNavigateToAiWriter}
              className="flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-purple-700 to-indigo-600 hover:from-purple-800 hover:to-indigo-700 px-3.5 py-2 text-xs font-bold text-white shadow-xs transition-all"
              title="เปิดระบบเขียนโครงการด้วย AI ตามแบบฟอร์ม สพฐ."
            >
              <Bot className="h-4 w-4 text-amber-300" />
              <span>ใช้ AI ช่วยเขียนโครงการ</span>
            </button>
          )}

          <button
            id="btn-add-new-project"
            type="button"
            onClick={handleOpenAddModal}
            className="flex items-center gap-1.5 rounded-lg bg-blue-700 hover:bg-blue-800 px-4 py-2 text-xs font-semibold text-white shadow-sm transition-colors"
          >
            <Plus className="h-4 w-4" />
            <span>เพิ่มโครงการใหม่</span>
          </button>
        </div>
      </div>

      {/* Proposal Window Banner */}
      {activeFiscalYear.isProposalOpen === false ? (
        <div className="rounded-xl p-4 bg-rose-50 border border-rose-200 text-rose-900 flex items-start gap-3 shadow-xs">
          <Lock className="h-5 w-5 text-rose-600 shrink-0 mt-0.5" />
          <div className="space-y-1">
            <div className="font-bold text-sm flex items-center gap-2">
              <span>สถานะ: ปิดรับการเสนอโครงการประจำปีงบประมาณ พ.ศ. {activeFiscalYear.year}</span>
              <span className="text-[11px] bg-rose-200 text-rose-800 px-2 py-0.5 rounded-full font-medium">
                ปิดระบบชั่วคราว
              </span>
            </div>
            <p className="text-xs text-rose-800">
              {activeFiscalYear.proposalNotice || 'ขณะนี้อยู่นอกช่วงเวลาการเสนอโครงการ หรือฝ่ายบริหารสถานศึกษาได้ทำการปิดรับข้อเสนอโครงการแล้ว'}
            </p>
            {activeFiscalYear.proposalCloseDate && (
              <p className="text-[11px] text-rose-700">
                (กำหนดปิดรับข้อเสนอเมื่อ: {activeFiscalYear.proposalCloseDate})
              </p>
            )}
          </div>
        </div>
      ) : (
        <div className="rounded-xl p-3 bg-emerald-50/70 border border-emerald-200 text-emerald-900 flex items-center justify-between gap-3 shadow-xs">
          <div className="flex items-center gap-2.5">
            <Unlock className="h-4 w-4 text-emerald-600 shrink-0" />
            <div className="text-xs">
              <span className="font-bold">เปิดรับการเสนอโครงการ:</span>{' '}
              <span>คุณครูสามารถเสนอโครงการเข้ามาเพื่อขอรับการจัดสรรงบประมาณแต่ละกลุ่มงานได้</span>
              {activeFiscalYear.proposalCloseDate && (
                <span className="ml-1 text-emerald-700 font-semibold">(สิ้นสุดวันที่ {activeFiscalYear.proposalCloseDate})</span>
              )}
            </div>
          </div>
          <span className="text-[11px] bg-emerald-100 text-emerald-800 border border-emerald-300 px-2 py-0.5 rounded-full font-semibold shrink-0">
            เปิดรับข้อเสนอ
          </span>
        </div>
      )}

      {/* Filter and Search Bar */}
      <div className="flex flex-col md:flex-row items-center justify-between gap-3 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
        <div className="relative w-full md:w-80">
          <Search className="h-4 w-4 absolute left-3 top-2.5 text-slate-400" />
          <input
            id="input-search-project"
            type="text"
            placeholder="ค้นหารหัส, ชื่อโครงการ, ผู้รับผิดชอบ..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full pl-9 pr-3 py-1.5 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div className="flex flex-wrap items-center gap-2 w-full md:w-auto">
          <div className="flex items-center gap-1.5 text-xs text-slate-600">
            <Filter className="h-3.5 w-3.5" />
            <span>ฝ่าย:</span>
            <select
              id="select-filter-dept"
              value={deptFilter}
              onChange={(e) => setDeptFilter(e.target.value)}
              className="text-xs rounded-lg border border-slate-300 bg-white py-1.5 px-2 focus:outline-none"
            >
              <option value="all">ทุกฝ่ายงาน</option>
              {departments.map((d) => (
                <option key={d.id} value={d.departmentName}>
                  {d.departmentName}
                </option>
              ))}
            </select>
          </div>

          <div className="flex items-center gap-1.5 text-xs text-slate-600">
            <span>สถานะ:</span>
            <select
              id="select-filter-status"
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="text-xs rounded-lg border border-slate-300 bg-white py-1.5 px-2 focus:outline-none"
            >
              <option value="all">ทุกสถานะ</option>
              <option value="not_started">ยังไม่ดำเนินการ</option>
              <option value="in_progress">อยู่ระหว่างดำเนินการ</option>
              <option value="completed">ดำเนินการแล้ว</option>
            </select>
          </div>
        </div>
      </div>

      {/* Projects Table */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs sm:text-sm">
            <thead>
              <tr className="bg-slate-100 border-b border-slate-200 text-slate-700 font-semibold">
                <th className="py-3 px-3 w-24">รหัส</th>
                <th className="py-3 px-3 min-w-[260px]">ชื่อโครงการ</th>
                <th className="py-3 px-3 w-32">ฝ่ายที่รับผิดชอบ</th>
                <th className="py-3 px-3 w-36">ผู้รับผิดชอบ</th>
                <th className="py-3 px-3 w-32 text-right">งบจัดสรร (บาท)</th>
                <th className="py-3 px-3 w-28 text-right">ใช้ไป (บาท)</th>
                <th className="py-3 px-3 w-28 text-right font-semibold text-emerald-700">คงเหลือ (บาท)</th>
                <th className="py-3 px-3 w-36 text-center">สถานะ</th>
                <th className="py-3 px-3 w-32 text-center">การอนุมัติ</th>
                <th className="py-3 px-3 w-36 text-center">จัดการ</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {filteredProjects.length === 0 ? (
                <tr>
                  <td colSpan={10} className="py-8 text-center text-slate-400">
                    ไม่พบโครงการตามเงื่อนไขที่ค้นหา
                  </td>
                </tr>
              ) : (
                filteredProjects.map((p) => {
                  return (
                    <tr key={p.id} className="hover:bg-slate-50/80 transition-colors">
                      <td className="py-3 px-3 font-mono font-bold text-blue-700">{p.projectCode}</td>
                      <td className="py-3 px-3 font-medium text-slate-900">
                        <div className="font-semibold text-slate-900">{p.projectName}</div>
                        <div className="text-[11px] text-slate-500 line-clamp-1">{p.objectives}</div>
                      </td>
                      <td className="py-3 px-3 text-slate-600">
                        <span className="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-700">
                          {p.department}
                        </span>
                      </td>
                      <td className="py-3 px-3 text-slate-700">
                        <div className="font-semibold text-slate-900">{p.responsiblePerson}</div>
                        {p.proposerCitizenId && (
                          <div className="text-[10px] font-mono text-blue-700 bg-blue-50 px-1.5 py-0.5 rounded inline-block mt-0.5 border border-blue-100" title="เลขประจำตัวประชาชนผู้เสนอโครงการ">
                            บัตร: {formatCitizenId(p.proposerCitizenId)}
                          </div>
                        )}
                        {p.attachmentName && (
                          <div className="text-[10px] text-slate-500 truncate max-w-[150px] mt-0.5" title={p.attachmentName}>
                            📎 {p.attachmentName}
                          </div>
                        )}
                      </td>
                      <td className="py-3 px-3 text-right font-mono font-bold text-slate-800">
                        {p.allocatedBudget.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                      </td>
                      <td className="py-3 px-3 text-right font-mono text-amber-600">
                        {p.spentBudget.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                      </td>
                      <td className="py-3 px-3 text-right font-mono font-bold text-emerald-600">
                        {p.remainingBudget.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                      </td>
                      <td className="py-3 px-3 text-center">
                        <select
                          value={p.status}
                          onChange={(e) => handleQuickStatusChange(p, e.target.value as any)}
                          className={`rounded-full px-2 py-1 text-[11px] font-semibold border-0 outline-none cursor-pointer ${
                            p.status === 'completed'
                              ? 'bg-emerald-100 text-emerald-800'
                              : p.status === 'in_progress'
                              ? 'bg-amber-100 text-amber-800'
                              : 'bg-slate-100 text-slate-700'
                          }`}
                        >
                          <option value="not_started">ยังไม่ดำเนินการ</option>
                          <option value="in_progress">อยู่ระหว่างดำเนิน</option>
                          <option value="completed">ดำเนินการแล้ว</option>
                        </select>
                      </td>
                      <td className="py-3 px-3 text-center">
                        {p.approvedBy ? (
                          <div className="text-[11px] text-emerald-700 flex flex-col items-center">
                            <span className="inline-flex items-center gap-1 font-semibold text-emerald-800">
                              <Check className="h-3 w-3" /> อนุมัติแล้ว
                            </span>
                            <span className="text-[10px] text-slate-500">{p.approvedDate}</span>
                          </div>
                        ) : currentUser.role === 'director' || currentUser.role === 'admin' ? (
                          <button
                            type="button"
                            onClick={() => handleApproveProject(p)}
                            className="inline-flex items-center gap-1 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 px-2 py-1 text-[11px] font-semibold transition-colors"
                          >
                            <ShieldCheck className="h-3.5 w-3.5" />
                            <span>อนุมัติ</span>
                          </button>
                        ) : (
                          <span className="text-[11px] text-slate-400">รอ ผอ. อนุมัติ</span>
                        )}
                      </td>
                      <td className="py-3 px-3 text-center">
                        <div className="flex items-center justify-center gap-1">
                          <button
                            type="button"
                            onClick={() => onOpenExpensesForProject(p)}
                            className="p-1.5 text-blue-600 hover:bg-blue-50 rounded transition-colors"
                            title="แตกรายละเอียดค่าใช้จ่ายโครงการ (เมนู 8)"
                          >
                            <FileSpreadsheet className="h-4 w-4" />
                          </button>
                          <button
                            type="button"
                            onClick={() => handleOpenEditModal(p)}
                            className="p-1.5 text-slate-600 hover:bg-slate-100 rounded transition-colors"
                            title="แก้ไขข้อมูลโครงการ"
                          >
                            <Edit className="h-4 w-4" />
                          </button>
                          <button
                            type="button"
                            onClick={() => handleDeleteProject(p.id)}
                            className="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                            title="ลบโครงการ"
                          >
                            <Trash2 className="h-4 w-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
            <tfoot>
              <tr className="bg-slate-900 text-white font-bold text-xs sm:text-sm">
                <td colSpan={4} className="py-3 px-3 text-right">
                  งบประมาณโครงการรวม ({filteredProjects.length} โครงการ):
                </td>
                <td className="py-3 px-3 text-right font-mono text-amber-300">
                  {filteredProjects.reduce((s, p) => s + p.allocatedBudget, 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                </td>
                <td className="py-3 px-3 text-right font-mono text-slate-300">
                  {filteredProjects.reduce((s, p) => s + p.spentBudget, 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                </td>
                <td className="py-3 px-3 text-right font-mono text-emerald-400">
                  {filteredProjects.reduce((s, p) => s + p.remainingBudget, 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}
                </td>
                <td colSpan={3} className="py-3 px-3 text-xs text-slate-400 font-normal">
                  บาท
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      {/* Modal: Add/Edit Project with all 16 requested fields */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 overflow-y-auto">
          <div className="bg-white rounded-2xl shadow-xl max-w-3xl w-full my-8 max-h-[90vh] flex flex-col overflow-hidden">
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-slate-50">
              <h3 className="text-base font-bold text-slate-900 flex items-center gap-2">
                <FolderGit2 className="h-5 w-5 text-blue-700" />
                <span>{editingProject ? 'แก้ไขข้อมูลโครงการ' : 'เพิ่มโครงการใหม่ตามแผนปฏิบัติการ'}</span>
              </h3>
              <button
                type="button"
                onClick={() => setIsModalOpen(false)}
                className="text-slate-400 hover:text-slate-700 rounded-lg p-1"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            <form onSubmit={handleSaveModal} className="flex-1 overflow-y-auto p-6 space-y-4 text-xs sm:text-sm">
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    รหัสโครงการ <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    required
                    value={formData.projectCode}
                    onChange={(e) => setFormData({ ...formData, projectCode: e.target.value })}
                    className="w-full rounded-lg border border-slate-300 p-2 font-mono font-bold focus:ring-2 focus:ring-blue-500 focus:outline-none"
                  />
                </div>

                <div className="sm:col-span-2">
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    ชื่อโครงการ <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    required
                    value={formData.projectName}
                    onChange={(e) => setFormData({ ...formData, projectName: e.target.value })}
                    className="w-full rounded-lg border border-slate-300 p-2 font-semibold text-slate-800 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">หลักการและเหตุผล</label>
                <textarea
                  rows={2}
                  value={formData.rationales}
                  onChange={(e) => setFormData({ ...formData, rationales: e.target.value })}
                  className="w-full rounded-lg border border-slate-300 p-2 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">วัตถุประสงค์</label>
                <textarea
                  rows={2}
                  value={formData.objectives}
                  onChange={(e) => setFormData({ ...formData, objectives: e.target.value })}
                  className="w-full rounded-lg border border-slate-300 p-2 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                />
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">เป้าหมายเชิงปริมาณ</label>
                  <input
                    type="text"
                    value={formData.quantitativeTarget}
                    onChange={(e) => setFormData({ ...formData, quantitativeTarget: e.target.value })}
                    className="w-full rounded-lg border border-slate-300 p-2 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">เป้าหมายเชิงคุณภาพ</label>
                  <input
                    type="text"
                    value={formData.qualitativeTarget}
                    onChange={(e) => setFormData({ ...formData, qualitativeTarget: e.target.value })}
                    className="w-full rounded-lg border border-slate-300 p-2 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">ตัวชี้วัดความสำเร็จ (KPI)</label>
                <input
                  type="text"
                  value={formData.kpi}
                  onChange={(e) => setFormData({ ...formData, kpi: e.target.value })}
                  className="w-full rounded-lg border border-slate-300 p-2 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">วิธีการดำเนินงาน / กิจกรรมสำคัญ</label>
                <textarea
                  rows={2}
                  value={formData.procedures}
                  onChange={(e) => setFormData({ ...formData, procedures: e.target.value })}
                  className="w-full rounded-lg border border-slate-300 p-2 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                />
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">ระยะเวลาดำเนินการ</label>
                  <input
                    type="text"
                    value={formData.duration}
                    onChange={(e) => setFormData({ ...formData, duration: e.target.value })}
                    className="w-full rounded-lg border border-slate-300 p-2 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">สถานที่ดำเนินการ</label>
                  <input
                    type="text"
                    value={formData.location}
                    onChange={(e) => setFormData({ ...formData, location: e.target.value })}
                    className="w-full rounded-lg border border-slate-300 p-2 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">กลุ่มเป้าหมาย</label>
                  <input
                    type="text"
                    value={formData.targetGroup}
                    onChange={(e) => setFormData({ ...formData, targetGroup: e.target.value })}
                    className="w-full rounded-lg border border-slate-300 p-2 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                  />
                </div>
              </div>

              {/* Teacher Proposer & Citizen ID Card */}
              <div className="bg-blue-50/60 p-4 rounded-xl border border-blue-200 space-y-3">
                <div className="flex items-center justify-between">
                  <span className="text-xs font-bold text-blue-900 flex items-center gap-1.5">
                    <UserCheck className="h-4 w-4 text-blue-700" />
                    ข้อมูลครูผู้เสนอโครงการ (Teacher Proposer)
                  </span>
                  <span className="text-[11px] text-blue-700 bg-white px-2 py-0.5 rounded-full border border-blue-200 font-medium">
                    ยืนยันตัวตนด้วยเลขบัตรประชาชน 13 หลัก
                  </span>
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs font-semibold text-slate-700 mb-1">
                      ชื่อ-สกุล ครูผู้เสนอโครงการ <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      required
                      value={formData.proposerName || formData.responsiblePerson || ''}
                      onChange={(e) => setFormData({ ...formData, proposerName: e.target.value, responsiblePerson: e.target.value })}
                      placeholder="เช่น ครูวิชัย ใจดี"
                      className="w-full rounded-lg border border-slate-300 p-2 text-xs bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold text-slate-700 mb-1">
                      เลขประจำตัวประชาชน 13 หลัก <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="text"
                      maxLength={13}
                      value={formData.proposerCitizenId || ''}
                      onChange={(e) => setFormData({ ...formData, proposerCitizenId: e.target.value.replace(/\D/g, '') })}
                      placeholder="เช่น 1234567890123"
                      className="w-full rounded-lg border border-slate-300 p-2 text-xs font-mono font-bold bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    />
                    <div className="text-[10px] mt-1 flex items-center justify-between">
                      <span className="font-mono text-slate-600">
                        {formData.proposerCitizenId ? formatCitizenId(formData.proposerCitizenId) : 'ระบุเลข 13 หลัก'}
                      </span>
                      <span className={formData.proposerCitizenId?.replace(/\D/g, '').length === 13 ? 'text-emerald-600 font-semibold' : 'text-amber-600'}>
                        {formData.proposerCitizenId?.replace(/\D/g, '').length === 13 ? '✓ ครบ 13 หลัก' : `(${formData.proposerCitizenId?.replace(/\D/g, '').length || 0}/13)`}
                      </span>
                    </div>
                  </div>
                </div>
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    เอกสารแนบโครงการ / ลิงก์รายละเอียดโครงการ (ถ้ามี)
                  </label>
                  <input
                    type="text"
                    value={formData.attachmentName || ''}
                    onChange={(e) => setFormData({ ...formData, attachmentName: e.target.value })}
                    placeholder="เช่น แบบเสนอโครงการ_ฉบับสมบูรณ์.pdf หรือระบุ URL ลิงก์ไฟล์"
                    className="w-full rounded-lg border border-slate-300 p-2 text-xs bg-white focus:ring-2 focus:ring-blue-500 focus:outline-none"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">ฝ่ายที่รับผิดชอบ</label>
                  <select
                    value={formData.department}
                    onChange={(e) => setFormData({ ...formData, department: e.target.value })}
                    className="w-full rounded-lg border border-slate-300 p-2 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                  >
                    {departments.map((d) => (
                      <option key={d.id} value={d.departmentName}>
                        {d.departmentName}
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">ผู้รับผิดชอบโครงการ</label>
                  <input
                    type="text"
                    value={formData.responsiblePerson}
                    onChange={(e) => setFormData({ ...formData, responsiblePerson: e.target.value })}
                    className="w-full rounded-lg border border-slate-300 p-2 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">แหล่งงบประมาณ</label>
                  <input
                    type="text"
                    value={formData.budgetSource}
                    onChange={(e) => setFormData({ ...formData, budgetSource: e.target.value })}
                    className="w-full rounded-lg border border-slate-300 p-2 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 p-3 rounded-lg border border-slate-200">
                <div>
                  <label className="block text-xs font-bold text-slate-800 mb-1">
                    งบประมาณที่ได้รับจัดสรร (บาท) <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="number"
                    min="0"
                    step="500"
                    required
                    value={formData.allocatedBudget}
                    onChange={(e) => setFormData({ ...formData, allocatedBudget: Number(e.target.value) || 0 })}
                    className="w-full rounded-lg border border-slate-300 p-2 text-sm font-bold font-mono text-blue-900 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                  />
                </div>
                <div>
                  <label className="block text-xs font-bold text-slate-800 mb-1">สถานะโครงการ</label>
                  <select
                    value={formData.status}
                    onChange={(e) => setFormData({ ...formData, status: e.target.value as any })}
                    className="w-full rounded-lg border border-slate-300 p-2 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-none"
                  >
                    <option value="not_started">ยังไม่ดำเนินการ</option>
                    <option value="in_progress">อยู่ระหว่างดำเนินการ</option>
                    <option value="completed">ดำเนินการแล้ว</option>
                  </select>
                </div>
              </div>

              <div className="flex justify-end gap-3 pt-4 border-t border-slate-200">
                <button
                  type="button"
                  onClick={() => setIsModalOpen(false)}
                  className="px-4 py-2 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                >
                  ยกเลิก
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 rounded-lg bg-blue-700 hover:bg-blue-800 text-xs font-semibold text-white shadow-sm"
                >
                  บันทึกข้อมูลโครงการ
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
