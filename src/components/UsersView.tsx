import React, { useState } from 'react';
import { User } from '../types';
import { ShieldCheck, UserPlus, Edit, Trash2, Check, X, KeyRound, UserCheck } from 'lucide-react';

interface UsersViewProps {
  users: User[];
  currentUser: User;
  onUpdateUsers: (updated: User[]) => void;
}

export const UsersView: React.FC<UsersViewProps> = ({
  users,
  currentUser,
  onUpdateUsers,
}) => {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingUser, setEditingUser] = useState<User | null>(null);

  const [formData, setFormData] = useState({
    username: '',
    fullName: '',
    email: '',
    role: 'teacher' as User['role'],
    position: 'ครูผู้รับผิดชอบโครงการ',
    password: '',
  });

  const handleOpenAdd = () => {
    setEditingUser(null);
    setFormData({
      username: '',
      fullName: '',
      email: '',
      role: 'teacher',
      position: 'ครูผู้รับผิดชอบโครงการ',
      password: '',
    });
    setIsModalOpen(true);
  };

  const handleOpenEdit = (u: User) => {
    setEditingUser(u);
    setFormData({
      username: u.username,
      fullName: u.fullName,
      email: u.email || '',
      role: u.role,
      position: u.position || '',
      password: '',
    });
    setIsModalOpen(true);
  };

  const handleDelete = (id: number) => {
    if (id === currentUser.id) {
      alert('ไม่สามารถลบบัญชีผู้ใช้งานที่กำลังล็อกอินอยู่ได้');
      return;
    }
    if (confirm('ต้องการลบผู้ใช้งานนี้ใช่หรือไม่?')) {
      onUpdateUsers(users.filter((u) => u.id !== id));
    }
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (editingUser) {
      const updated = users.map((u) =>
        u.id === editingUser.id
          ? {
              ...u,
              username: formData.username,
              fullName: formData.fullName,
              email: formData.email,
              role: formData.role,
              position: formData.position,
            }
          : u
      );
      onUpdateUsers(updated);
    } else {
      const newId = users.length > 0 ? Math.max(...users.map((u) => u.id)) + 1 : 1;
      const newUser: User = {
        id: newId,
        schoolId: 1,
        username: formData.username,
        fullName: formData.fullName,
        email: formData.email,
        role: formData.role,
        position: formData.position,
        isActive: true,
      };
      onUpdateUsers([...users, newUser]);
    }
    setIsModalOpen(false);
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 pb-4">
        <div>
          <h2 className="text-xl font-bold text-slate-900 flex items-center gap-2">
            <ShieldCheck className="h-6 w-6 text-blue-700" />
            <span>ระบบผู้ใช้งานและความปลอดภัย (User Management & RBAC)</span>
          </h2>
          <p className="text-xs text-slate-500 mt-0.5">
            กำหนดระดับสิทธิ์การเข้าถึง 3 ระดับ: Administrator, ผู้อำนวยการโรงเรียน, ครู/ผู้รับผิดชอบโครงการ
          </p>
        </div>

        <button
          id="btn-add-user"
          type="button"
          onClick={handleOpenAdd}
          className="flex items-center gap-1.5 rounded-lg bg-blue-700 hover:bg-blue-800 px-4 py-2 text-xs font-semibold text-white shadow-sm transition-colors"
        >
          <UserPlus className="h-4 w-4" />
          <span>เพิ่มผู้ใช้งานใหม่</span>
        </button>
      </div>

      {/* Role explanation boxes */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div className="rounded-xl border border-blue-200 bg-blue-50/70 p-4">
          <div className="text-xs font-bold text-blue-950 flex items-center gap-1.5">
            <span className="h-2.5 w-2.5 rounded-full bg-blue-700"></span>
            <span>Administrator (ผู้ดูแลระบบ)</span>
          </div>
          <p className="text-[11px] text-blue-800 mt-1">
            สิทธิ์สูงสุด จัดการข้อมูลโรงเรียน นักเรียน ประมาณการรายรับ จัดสรรงบประมาณ โครงการ และผู้ใช้งาน
          </p>
        </div>

        <div className="rounded-xl border border-amber-200 bg-amber-50/70 p-4">
          <div className="text-xs font-bold text-amber-950 flex items-center gap-1.5">
            <span className="h-2.5 w-2.5 rounded-full bg-amber-600"></span>
            <span>ผู้อำนวยการโรงเรียน (Director)</span>
          </div>
          <p className="text-[11px] text-amber-900 mt-1">
            ดูภาพรวม Dashboard อนุมัติโครงการในแผน ตรวจสอบการเบิกจ่าย และพิมพ์รายงานราชการ
          </p>
        </div>

        <div className="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4">
          <div className="text-xs font-bold text-emerald-950 flex items-center gap-1.5">
            <span className="h-2.5 w-2.5 rounded-full bg-emerald-600"></span>
            <span>ครู / ผู้รับผิดชอบโครงการ (Teacher)</span>
          </div>
          <p className="text-[11px] text-emerald-900 mt-1">
            เขียนเสนอโครงการ แจกแจงค่าใช้จ่าย บันทึกการเบิกจ่ายเงินงบประมาณ และติดตามงบคงเหลือ
          </p>
        </div>
      </div>

      {/* Users Table */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs sm:text-sm">
            <thead>
              <tr className="bg-slate-100 border-b border-slate-200 text-slate-700 font-semibold">
                <th className="py-3 px-4 w-12 text-center">ที่</th>
                <th className="py-3 px-4 min-w-[140px]">ชื่อผู้ใช้ (Username)</th>
                <th className="py-3 px-4 min-w-[200px]">ชื่อ - นามสกุล</th>
                <th className="py-3 px-4 min-w-[160px]">ตำแหน่ง</th>
                <th className="py-3 px-4 w-40 text-center">ระดับสิทธิ์ (Role)</th>
                <th className="py-3 px-4 min-w-[180px]">อีเมล</th>
                <th className="py-3 px-4 w-24 text-center">สถานะ</th>
                <th className="py-3 px-4 w-28 text-center">จัดการ</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {users.map((u, idx) => (
                <tr key={u.id} className="hover:bg-slate-50/80 transition-colors">
                  <td className="py-3 px-4 text-center text-slate-400 font-mono">{idx + 1}</td>
                  <td className="py-3 px-4 font-mono font-bold text-blue-700">{u.username}</td>
                  <td className="py-3 px-4 font-semibold text-slate-900">{u.fullName}</td>
                  <td className="py-3 px-4 text-slate-600">{u.position}</td>
                  <td className="py-3 px-4 text-center">
                    <span
                      className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                        u.role === 'admin'
                          ? 'bg-blue-100 text-blue-800'
                          : u.role === 'director'
                          ? 'bg-amber-100 text-amber-800'
                          : 'bg-emerald-100 text-emerald-800'
                      }`}
                    >
                      {u.role === 'admin'
                        ? 'Administrator'
                        : u.role === 'director'
                        ? 'ผู้อำนวยการ'
                        : 'ครู/ผู้รับผิดชอบ'}
                    </span>
                  </td>
                  <td className="py-3 px-4 text-slate-500 text-xs">{u.email || '-'}</td>
                  <td className="py-3 px-4 text-center">
                    <span className="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-800">
                      ใช้งานปกติ
                    </span>
                  </td>
                  <td className="py-3 px-4 text-center">
                    <div className="flex items-center justify-center gap-1">
                      <button
                        type="button"
                        onClick={() => handleOpenEdit(u)}
                        className="p-1.5 text-slate-600 hover:bg-slate-100 rounded transition-colors"
                        title="แก้ไขข้อมูลผู้ใช้"
                      >
                        <Edit className="h-4 w-4" />
                      </button>
                      <button
                        type="button"
                        onClick={() => handleDelete(u.id)}
                        disabled={u.id === currentUser.id}
                        className={`p-1.5 rounded transition-colors ${
                          u.id === currentUser.id
                            ? 'text-slate-300 cursor-not-allowed'
                            : 'text-slate-400 hover:text-red-600 hover:bg-red-50'
                        }`}
                        title="ลบผู้ใช้"
                      >
                        <Trash2 className="h-4 w-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Modal: Add/Edit User */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
          <div className="bg-white rounded-2xl shadow-xl max-w-md w-full overflow-hidden">
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-slate-50">
              <h3 className="text-base font-bold text-slate-900 flex items-center gap-2">
                <ShieldCheck className="h-5 w-5 text-blue-700" />
                <span>{editingUser ? 'แก้ไขข้อมูลผู้ใช้งาน' : 'เพิ่มผู้ใช้งานระบบใหม่'}</span>
              </h3>
              <button
                type="button"
                onClick={() => setIsModalOpen(false)}
                className="text-slate-400 hover:text-slate-700"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleSubmit} className="p-6 space-y-4 text-xs sm:text-sm">
              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  ชื่อผู้ใช้ (Username สำหรับ Login) <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  required
                  value={formData.username}
                  onChange={(e) => setFormData({ ...formData, username: e.target.value })}
                  className="w-full rounded-lg border border-slate-300 p-2 font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  ชื่อ - นามสกุล <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  required
                  value={formData.fullName}
                  onChange={(e) => setFormData({ ...formData, fullName: e.target.value })}
                  className="w-full rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">ตำแหน่ง</label>
                <input
                  type="text"
                  value={formData.position}
                  onChange={(e) => setFormData({ ...formData, position: e.target.value })}
                  className="w-full rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">ระดับสิทธิ์ (Role)</label>
                <select
                  value={formData.role}
                  onChange={(e) => setFormData({ ...formData, role: e.target.value as any })}
                  className="w-full rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none font-semibold text-slate-800"
                >
                  <option value="admin">Administrator (ผู้ดูแลระบบ)</option>
                  <option value="director">ผู้อำนวยการโรงเรียน (Director)</option>
                  <option value="teacher">ครู / ผู้รับผิดชอบโครงการ (Teacher)</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">อีเมล</label>
                <input
                  type="email"
                  value={formData.email}
                  onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                  className="w-full rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  {editingUser ? 'รหัสผ่านใหม่ (เว้นว่างไว้ถ้าไม่ต้องการเปลี่ยน)' : 'รหัสผ่าน (Password)'}
                </label>
                <input
                  type="password"
                  placeholder={editingUser ? '••••••••' : 'ระบุรหัสผ่านอย่างน้อย 6 ตัวอักษร'}
                  value={formData.password}
                  onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                  className="w-full rounded-lg border border-slate-300 p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                />
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
                  บันทึกผู้ใช้งาน
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
