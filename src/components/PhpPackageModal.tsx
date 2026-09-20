import React, { useState, useEffect } from 'react';
import { 
  X, 
  DownloadCloud, 
  FileCode, 
  Copy, 
  Check, 
  Server, 
  FolderArchive,
  Search,
  BookOpen,
  Terminal,
  Database,
  Bot,
  Layers,
  ArrowDownToLine
} from 'lucide-react';

interface PhpPackageModalProps {
  isOpen: boolean;
  onClose: () => void;
}

interface PhpFileItem {
  path: string;
  name: string;
  category: string;
  size: number;
  content: string;
}

export const PhpPackageModal: React.FC<PhpPackageModalProps> = ({ isOpen, onClose }) => {
  const [files, setFiles] = useState<PhpFileItem[]>([]);
  const [selectedFile, setSelectedFile] = useState<PhpFileItem | null>(null);
  const [loading, setLoading] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [activeCategory, setActiveCategory] = useState<string>('all');
  const [copied, setCopied] = useState(false);
  const [isDownloadingZip, setIsDownloadingZip] = useState(false);

  useEffect(() => {
    if (isOpen) {
      fetchPhpFiles();
    }
  }, [isOpen]);

  const fetchPhpFiles = async () => {
    setLoading(true);
    try {
      const res = await fetch('/api/php-files');
      if (res.ok) {
        const data = await res.json();
        if (data.success && data.files) {
          setFiles(data.files);
          const defaultFile = data.files.find((f: PhpFileItem) => f.path === 'index.php') || data.files[0];
          setSelectedFile(defaultFile);
        }
      }
    } catch (e) {
      console.error('Failed to load PHP files from server:', e);
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  const categories = [
    { id: 'all', label: 'ทั้งหมด' },
    { id: 'root', label: 'หน้าเว็บหลัก (Root)' },
    { id: 'includes', label: 'Includes & Templates' },
    { id: 'api', label: 'API & AI Engine' },
    { id: 'config', label: 'Config & Database' },
    { id: 'database', label: 'SQL Schemas' },
  ];

  const filteredFiles = files.filter((f) => {
    const matchesCategory = activeCategory === 'all' || f.category === activeCategory;
    const matchesSearch = f.path.toLowerCase().includes(searchQuery.toLowerCase()) ||
                          f.name.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesCategory && matchesSearch;
  });

  const handleCopy = () => {
    if (!selectedFile) return;
    navigator.clipboard.writeText(selectedFile.content);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const handleDownloadSingle = () => {
    if (!selectedFile) return;
    const blob = new Blob([selectedFile.content], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = selectedFile.name;
    link.click();
    URL.revokeObjectURL(url);
  };

  const handleDownloadZip = () => {
    setIsDownloadingZip(true);
    const link = document.createElement('a');
    link.href = '/api/download-php-zip';
    link.download = 'school-budget-php-system.zip';
    link.click();
    setTimeout(() => setIsDownloadingZip(false), 2000);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 p-3 sm:p-6 backdrop-blur-xs">
      <div className="bg-white rounded-2xl shadow-2xl max-w-6xl w-full h-[90vh] flex flex-col overflow-hidden border border-slate-200">
        
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between px-6 py-4 border-b border-slate-200 bg-slate-950 text-white gap-3 shrink-0">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white shadow-md">
              <Server className="h-5 w-5" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h3 className="text-base font-bold text-white">
                  ระบบแผนปฏิบัติการประจำปีโรงเรียน (PHP 8.x + MySQL Edition)
                </h3>
                <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                  Native PHP 100%
                </span>
              </div>
              <p className="text-xs text-slate-400">
                แปลงไฟล์ทั้งหมดเป็นภาษา PHP พร้อมฐานข้อมูล MySQL และระบบร่างโครงการ AI (Word & PDF)
              </p>
            </div>
          </div>

          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={handleDownloadZip}
              disabled={isDownloadingZip}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold shadow-md transition-all cursor-pointer"
            >
              <FolderArchive className="h-4 w-4 text-emerald-200" />
              <span>{isDownloadingZip ? 'กำลังเริ่มดาวน์โหลด...' : 'ดาวน์โหลด ZIP ครบทุกไฟล์'}</span>
            </button>
            <button
              type="button"
              onClick={onClose}
              className="text-slate-400 hover:text-white rounded-xl p-1.5 hover:bg-slate-800 transition-colors"
            >
              <X className="h-5 w-5" />
            </button>
          </div>
        </div>

        {/* Main Body */}
        <div className="flex-1 flex flex-col md:flex-row min-h-0 overflow-hidden">
          
          {/* Left Sidebar: File List */}
          <div className="w-full md:w-80 border-r border-slate-200 flex flex-col bg-slate-50 shrink-0">
            {/* Search & Category Filter */}
            <div className="p-3 border-b border-slate-200 bg-white space-y-2">
              <div className="relative">
                <Search className="w-3.5 h-3.5 absolute left-2.5 top-3 text-slate-400" />
                <input
                  type="text"
                  placeholder="ค้นหาชื่อไฟล์ (เช่น index, ai...)"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50"
                />
              </div>

              <div className="flex flex-wrap gap-1">
                {categories.map((cat) => (
                  <button
                    key={cat.id}
                    onClick={() => setActiveCategory(cat.id)}
                    className={`px-2 py-1 text-[11px] font-semibold rounded-md transition-colors ${
                      activeCategory === cat.id
                        ? 'bg-blue-600 text-white'
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                    }`}
                  >
                    {cat.label}
                  </button>
                ))}
              </div>
            </div>

            {/* File Items Scrollable */}
            <div className="flex-1 overflow-y-auto p-2 space-y-1">
              {loading ? (
                <div className="p-4 text-center text-xs text-slate-500">กำลังโหลดรายการไฟล์...</div>
              ) : filteredFiles.length === 0 ? (
                <div className="p-4 text-center text-xs text-slate-400">ไม่พบไฟล์ที่ตรงกับคำค้นหา</div>
              ) : (
                filteredFiles.map((file) => {
                  const isSelected = selectedFile?.path === file.path;
                  return (
                    <button
                      key={file.path}
                      onClick={() => setSelectedFile(file)}
                      className={`w-full text-left px-3 py-2 rounded-lg text-xs transition-all flex items-center justify-between ${
                        isSelected
                          ? 'bg-blue-600 text-white shadow-xs font-semibold'
                          : 'text-slate-700 hover:bg-slate-200/70'
                      }`}
                    >
                      <div className="flex items-center gap-2 truncate">
                        <FileCode className={`w-4 h-4 shrink-0 ${isSelected ? 'text-white' : 'text-blue-600'}`} />
                        <span className="truncate">{file.path}</span>
                      </div>
                      <span className={`text-[10px] font-mono shrink-0 ${isSelected ? 'text-blue-100' : 'text-slate-400'}`}>
                        {Math.round(file.size / 1024)} KB
                      </span>
                    </button>
                  );
                })
              )}
            </div>

            {/* Bottom Quick Guide Button */}
            <div className="p-3 border-t border-slate-200 bg-white text-xs">
              <div className="text-slate-500 text-[11px] mb-1 font-semibold">
                พร้อมใช้งานบน:
              </div>
              <div className="flex items-center gap-1.5 text-[11px] text-slate-700">
                <span className="px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200 font-mono">cPanel</span>
                <span className="px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200 font-mono">DirectAdmin</span>
                <span className="px-1.5 py-0.5 rounded bg-slate-100 border border-slate-200 font-mono">XAMPP</span>
              </div>
            </div>
          </div>

          {/* Right Area: Code Viewer */}
          <div className="flex-1 flex flex-col min-w-0 bg-slate-950">
            {/* Top Toolbar for Selected File */}
            <div className="px-5 py-3 border-b border-slate-800 bg-slate-900/90 flex items-center justify-between gap-4 shrink-0">
              <div className="flex items-center gap-2.5 truncate">
                <FileCode className="w-4 h-4 text-emerald-400 shrink-0" />
                <span className="font-mono text-xs font-bold text-white truncate">
                  {selectedFile ? selectedFile.path : 'เลือกไฟล์เพื่อดูโค้ด'}
                </span>
                {selectedFile && (
                  <span className="text-[11px] text-slate-400 font-mono">
                    ({(selectedFile.content.match(/\n/g) || []).length + 1} บรรทัด • {selectedFile.size.toLocaleString()} ไบต์)
                  </span>
                )}
              </div>

              <div className="flex items-center gap-2 shrink-0">
                <button
                  type="button"
                  onClick={handleCopy}
                  className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition-colors"
                >
                  {copied ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
                  <span>{copied ? 'คัดลอกสำเร็จ' : 'คัดลอกโค้ด'}</span>
                </button>
                <button
                  type="button"
                  onClick={handleDownloadSingle}
                  className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold transition-colors"
                >
                  <ArrowDownToLine className="w-3.5 h-3.5" />
                  <span>ดาวน์โหลดไฟล์นี้</span>
                </button>
              </div>
            </div>

            {/* Code Content */}
            <div className="flex-1 overflow-auto p-4 sm:p-6 font-mono text-xs text-slate-200 leading-relaxed custom-scrollbar selection:bg-blue-600 selection:text-white">
              {selectedFile ? (
                <pre className="whitespace-pre">{selectedFile.content}</pre>
              ) : (
                <div className="h-full flex items-center justify-center text-slate-500">
                  กรุณาเลือกไฟล์ทางด้านซ้ายเพื่อดูโค้ด PHP
                </div>
              )}
            </div>

            {/* Bottom Info Bar */}
            <div className="px-5 py-2.5 border-t border-slate-800 bg-slate-900 flex flex-col sm:flex-row items-center justify-between text-[11px] text-slate-400 shrink-0 gap-2">
              <div className="flex items-center gap-2">
                <span className="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span>มาตรฐานระเบียบพัสดุและการเงิน สพฐ. กระทรวงศึกษาธิการ</span>
              </div>
              <div className="text-slate-400">
                ติดตั้งง่าย: วางไฟล์ลง <code className="text-emerald-400 font-mono">public_html</code> และเปิดผ่านเบราว์เซอร์ได้ทันที
              </div>
            </div>
          </div>

        </div>

      </div>
    </div>
  );
};
