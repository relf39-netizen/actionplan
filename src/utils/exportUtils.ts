import * as XLSX from 'xlsx';
import { jsPDF } from 'jspdf';
import { School, FiscalYear, StudentLevel, RevenueItem, BudgetAllocation, Project, BudgetTransaction } from '../types';

/**
 * ส่งออกตารางข้อมูลเป็นไฟล์ Excel (.xlsx)
 */
export function exportToExcel(
  sheetName: string,
  fileName: string,
  data: Record<string, any>[]
) {
  const worksheet = XLSX.utils.json_to_sheet(data);
  const workbook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(workbook, worksheet, sheetName);
  XLSX.writeFile(workbook, `${fileName}.xlsx`);
}

/**
 * ส่งออกรายงานเป็น PDF แบบภาษาไทยพร้อมหัวเอกสารราชการ
 */
export function exportToPdf(
  title: string,
  schoolName: string,
  fiscalYear: number,
  headers: string[],
  rows: string[][],
  fileName: string
) {
  const doc = new jsPDF({
    orientation: 'landscape',
    unit: 'mm',
    format: 'a4',
  });

  doc.setFontSize(16);
  doc.text(title, 14, 18);
  doc.setFontSize(11);
  doc.text(`${schoolName} | ประจำปีงบประมาณ พ.ศ. ${fiscalYear}`, 14, 25);
  doc.text(`วันที่พิมพ์รายงาน: ${new Date().toLocaleDateString('th-TH')}`, 14, 31);

  let startY = 38;
  const colWidth = 270 / headers.length;

  // Header row
  doc.setFillColor(37, 99, 235);
  doc.rect(14, startY, 270, 9, 'F');
  doc.setTextColor(255, 255, 255);
  doc.setFontSize(10);
  headers.forEach((h, i) => {
    doc.text(h, 16 + i * colWidth, startY + 6);
  });

  startY += 9;
  doc.setTextColor(30, 41, 59);

  rows.forEach((row, rowIndex) => {
    if (startY > 185) {
      doc.addPage();
      startY = 20;
    }
    if (rowIndex % 2 === 1) {
      doc.setFillColor(241, 245, 249);
      doc.rect(14, startY, 270, 8, 'F');
    }
    row.forEach((cell, cellIndex) => {
      const text = String(cell || '').substring(0, 32);
      doc.text(text, 16 + cellIndex * colWidth, startY + 5.5);
    });
    startY += 8;
  });

  doc.save(`${fileName}.pdf`);
}

function sqlEscape(val?: string | null): string {
  if (!val) return '';
  return val.split("'").join("''");
}

/**
 * สร้างไฟล์ SQL Dump จากข้อมูลปัจจุบันในระบบ
 */
export function generateSqlDump(
  school: School,
  fiscalYear: FiscalYear,
  students: StudentLevel[],
  revenues: RevenueItem[],
  allocations: BudgetAllocation[],
  projects: Project[],
  transactions: BudgetTransaction[]
): string {
  const lines: string[] = [
    '-- ==========================================================',
    `-- SQL Dump: ข้อมูลแผนปฏิบัติการประจำปีและจัดสรรงบประมาณ`,
    `-- โรงเรียน: ${school.name} (ปีงบประมาณ ${fiscalYear.year})`,
    `-- ส่งออกเมื่อ: ${new Date().toISOString()}`,
    '-- ==========================================================',
    '',
    `UPDATE schools SET name='${sqlEscape(school.name)}', director_name='${sqlEscape(school.directorName)}', phone='${sqlEscape(school.phone)}', email='${sqlEscape(school.email)}' WHERE id=${school.id};`,
    '',
  ];

  // Students
  students.forEach((s) => {
    lines.push(
      `INSERT INTO students (id, school_id, fiscal_year_id, grade_level, male_count, female_count, total_count) VALUES (${s.id}, ${s.schoolId}, ${s.fiscalYearId}, '${sqlEscape(s.gradeLevel)}', ${s.maleCount}, ${s.femaleCount}, ${s.totalCount}) ON DUPLICATE KEY UPDATE male_count=${s.maleCount}, female_count=${s.femaleCount}, total_count=${s.totalCount};`
    );
  });
  lines.push('');

  // Revenues
  revenues.forEach((r) => {
    lines.push(
      `INSERT INTO revenues (id, school_id, fiscal_year_id, category, item_name, rate_per_head, eligible_count, calculated_amount, note) VALUES (${r.id}, ${r.schoolId}, ${r.fiscalYearId}, '${sqlEscape(r.category)}', '${sqlEscape(r.itemName)}', ${r.ratePerHead}, ${r.eligibleCount}, ${r.calculatedAmount}, '${sqlEscape(r.note || '')}') ON DUPLICATE KEY UPDATE calculated_amount=${r.calculatedAmount};`
    );
  });
  lines.push('');

  // Allocations
  allocations.forEach((a) => {
    lines.push(
      `INSERT INTO budget_allocations (id, school_id, fiscal_year_id, department_name, percentage, allocated_amount, spent_amount, remaining_amount) VALUES (${a.id}, ${a.schoolId}, ${a.fiscalYearId}, '${sqlEscape(a.departmentName)}', ${a.percentage}, ${a.allocatedAmount}, ${a.spentAmount}, ${a.remainingAmount}) ON DUPLICATE KEY UPDATE percentage=${a.percentage}, allocated_amount=${a.allocatedAmount};`
    );
  });
  lines.push('');

  // Projects
  projects.forEach((p) => {
    lines.push(
      `INSERT INTO projects (id, school_id, fiscal_year_id, project_code, project_name, department, budget_source, allocated_budget, spent_budget, remaining_budget, status, responsible_person) VALUES (${p.id}, ${p.schoolId}, ${p.fiscalYearId}, '${sqlEscape(p.projectCode)}', '${sqlEscape(p.projectName)}', '${sqlEscape(p.department)}', '${sqlEscape(p.budgetSource)}', ${p.allocatedBudget}, ${p.spentBudget}, ${p.remainingBudget}, '${sqlEscape(p.status)}', '${sqlEscape(p.responsiblePerson)}') ON DUPLICATE KEY UPDATE allocated_budget=${p.allocatedBudget}, spent_budget=${p.spentBudget};`
    );
  });
  lines.push('');

  // Transactions
  transactions.forEach((t) => {
    lines.push(
      `INSERT INTO budget_transactions (id, school_id, fiscal_year_id, project_id, transaction_date, item_description, amount, payee, doc_number, note, recorded_by) VALUES (${t.id}, ${t.schoolId}, ${t.fiscalYearId}, ${t.projectId}, '${t.transactionDate}', '${sqlEscape(t.itemDescription)}', ${t.amount}, '${sqlEscape(t.payee)}', '${sqlEscape(t.docNumber)}', '${sqlEscape(t.note || '')}', '${sqlEscape(t.recordedBy || '')}');`
    );
  });

  return lines.join('\n');
}

/**
 * ส่งออกข้อเสนอโครงการเป็นไฟล์เอกสาร Word (.doc / .docx compatible) 
 * ที่สามารถเปิดและแก้ไขต่อใน Microsoft Word / Google Docs ได้ทันที
 */
export function exportProjectProposalToWordDoc(
  proposal: any,
  school: School,
  fiscalYear: FiscalYear
) {
  const expenseRowsHtml = (proposal.expenseItems || [])
    .map(
      (it: any, idx: number) => `
    <tr>
      <td style="border: 1px solid #333; padding: 6px; text-align: center;">${idx + 1}</td>
      <td style="border: 1px solid #333; padding: 6px;">${it.itemName || ''}</td>
      <td style="border: 1px solid #333; padding: 6px; text-align: center;">${it.category || ''}</td>
      <td style="border: 1px solid #333; padding: 6px; text-align: center;">${it.quantity || 1}</td>
      <td style="border: 1px solid #333; padding: 6px; text-align: center;">${it.unit || 'ชุด'}</td>
      <td style="border: 1px solid #333; padding: 6px; text-align: right;">${Number(it.unitPrice || 0).toLocaleString()}</td>
      <td style="border: 1px solid #333; padding: 6px; text-align: right; font-weight: bold;">${Number(it.totalAmount || 0).toLocaleString()}</td>
    </tr>
  `
    )
    .join('');

  const activityRowsHtml = (proposal.activities || [])
    .map(
      (act: any) => `
    <tr>
      <td style="border: 1px solid #333; padding: 6px; font-weight: bold;">${act.phase || ''}</td>
      <td style="border: 1px solid #333; padding: 6px;">${act.description || ''}</td>
      <td style="border: 1px solid #333; padding: 6px; text-align: center;">${act.duration || ''}</td>
      <td style="border: 1px solid #333; padding: 6px; text-align: center;">${act.responsible || ''}</td>
    </tr>
  `
    )
    .join('');

  const objectivesHtml = (proposal.objectives || [])
    .map((obj: string, i: number) => `<p style="margin: 4px 0 4px 24px;">6.${i + 1} ${obj}</p>`)
    .join('');

  const benefitsHtml = (proposal.expectedBenefits || [])
    .map((b: string, i: number) => `<p style="margin: 4px 0 4px 24px;">12.${i + 1} ${b}</p>`)
    .join('');

  const htmlContent = `
    <html xmlns:o='urn:schemas-microsoft-com:office:office' 
          xmlns:w='urn:schemas-microsoft-com:office:word' 
          xmlns='http://www.w3.org/TR/REC-html40'>
    <head>
      <meta charset='utf-8'>
      <title>${proposal.projectName || 'แบบเสนอโครงการ'}</title>
      <!--[if gte mso 9]>
      <xml>
        <w:WordDocument>
          <w:View>Print</w:View>
          <w:Zoom>100</w:Zoom>
          <w:DoNotOptimizeForBrowser/>
        </w:WordDocument>
      </xml>
      <![endif]-->
      <style>
        @page Section1 {
          size: 210mm 297mm;
          margin: 25.4mm 25.4mm 25.4mm 25.4mm;
        }
        div.Section1 { page: Section1; }
        body {
          font-family: 'TH Sarabun PSK', 'TH Sarabun New', 'Angsana New', 'Cordia New', sans-serif;
          font-size: 16pt;
          line-height: 1.3;
          color: #000000;
        }
        h1 { font-size: 18pt; font-weight: bold; text-align: center; margin-bottom: 4px; }
        h2 { font-size: 16pt; font-weight: bold; text-align: center; margin-top: 0; margin-bottom: 24px; }
        p { margin: 6px 0; text-align: justify; }
        .section-title { font-weight: bold; margin-top: 14px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; font-size: 14pt; }
        th { border: 1px solid #333; padding: 6px; background-color: #f2f2f2; text-align: center; font-weight: bold; }
        td { border: 1px solid #333; padding: 6px; }
      </style>
    </head>
    <body>
      <div class="Section1">
        <h1>แบบเสนอโครงการตามแผนปฏิบัติการประจำปีงบประมาณ พ.ศ. ${fiscalYear.year}</h1>
        <h2>${school.name} (${school.educationArea || school.affiliation})</h2>

        <p class="section-title">1. ชื่อโครงการ: <span style="font-weight: normal;">${proposal.projectName || ''}</span></p>
        <p class="section-title">2. รหัสโครงการ: <span style="font-weight: normal;">${proposal.projectCode || 'รอออกรหัส'}</span></p>
        <p class="section-title">3. ลักษณะโครงการ: <span style="font-weight: normal;">โครงการ${proposal.projectType || 'ใหม่'}</span></p>
        <p class="section-title">4. ความสอดคล้องกับยุทธศาสตร์ / นโยบาย:</p>
        <p style="margin-left: 24px;">- ${proposal.strategyAlignment || 'ยุทธศาสตร์พัฒนาคุณภาพการศึกษา สพฐ.'}</p>
        
        <p class="section-title">5. กลุ่มงาน / ผู้รับผิดชอบโครงการ:</p>
        <p style="margin-left: 24px;">กลุ่มงาน/ฝ่าย: <strong>${proposal.department || ''}</strong> | ผู้รับผิดชอบ: <strong>${proposal.responsiblePerson || ''}</strong> ${proposal.position ? `(${proposal.position})` : ''}</p>

        <p class="section-title">6. วัตถุประสงค์:</p>
        ${objectivesHtml || '<p style="margin-left: 24px;">- เพื่อพัฒนาคุณภาพการจัดการเรียนรู้</p>'}

        <p class="section-title">7. หลักการและเหตุผล:</p>
        <p style="text-indent: 40px; margin-left: 10px;">${proposal.rationale || ''}</p>

        <p class="section-title">8. เป้าหมาย:</p>
        <p style="margin-left: 24px;"><strong>8.1 เป้าหมายเชิงปริมาณ:</strong> ${proposal.quantitativeTarget || ''}</p>
        <p style="margin-left: 24px;"><strong>8.2 เป้าหมายเชิงคุณภาพ:</strong> ${proposal.qualitativeTarget || ''}</p>

        <p class="section-title">9. สถานที่และระยะเวลาดำเนินการ:</p>
        <p style="margin-left: 24px;">สถานที่: ${proposal.location || 'โรงเรียน'} | ระยะเวลา: ${proposal.timeline || 'ตลอดปีการศึกษา'}</p>

        <p class="section-title">10. ขั้นตอนและปฏิทินการดำเนินงาน (PDCA):</p>
        <table>
          <thead>
            <tr>
              <th style="width: 25%;">ขั้นตอนการดำเนินงาน</th>
              <th style="width: 45%;">รายละเอียดกิจกรรม</th>
              <th style="width: 15%;">ระยะเวลา</th>
              <th style="width: 15%;">ผู้รับผิดชอบ</th>
            </tr>
          </thead>
          <tbody>
            ${activityRowsHtml}
          </tbody>
        </table>

        <p class="section-title">11. งบประมาณและรายละเอียดค่าใช้จ่าย:</p>
        <p style="margin-left: 10px;">งบประมาณรวมทั้งสิ้น <strong>${Number(proposal.totalBudget || 0).toLocaleString()} บาท</strong> จากแหล่งงบประมาณ: ${proposal.budgetSource || 'เงินอุดหนุน สพฐ.'}</p>
        <table>
          <thead>
            <tr>
              <th style="width: 6%;">ที่</th>
              <th style="width: 38%;">รายการค่าใช้จ่าย</th>
              <th style="width: 16%;">หมวดรายจ่าย</th>
              <th style="width: 8%;">จำนวน</th>
              <th style="width: 8%;">หน่วย</th>
              <th style="width: 12%;">ราคา/หน่วย</th>
              <th style="width: 12%;">รวมเงิน (บาท)</th>
            </tr>
          </thead>
          <tbody>
            ${expenseRowsHtml}
            <tr style="font-weight: bold; background-color: #f9f9f9;">
              <td colspan="6" style="border: 1px solid #333; padding: 6px; text-align: right;">รวมงบประมาณทั้งสิ้น</td>
              <td style="border: 1px solid #333; padding: 6px; text-align: right;">${Number(proposal.totalBudget || 0).toLocaleString()}</td>
            </tr>
          </tbody>
        </table>

        <p class="section-title">12. ผลที่คาดว่าจะได้รับ:</p>
        ${benefitsHtml}

        <p class="section-title">13. การประเมินผลและตัวชี้วัดความสำเร็จ:</p>
        <p style="margin-left: 24px;"><strong>ตัวชี้วัด (KPI):</strong> ${proposal.kpis || ''}</p>
        <p style="margin-left: 24px;"><strong>วิธีการและเครื่องมือประเมิน:</strong> ${proposal.evaluationMethods || ''}</p>

        <table style="width: 100%; border: none; margin-top: 40px; page-break-inside: avoid;">
          <tr style="border: none;">
            <td style="width: 50%; border: none; text-align: center; vertical-align: top; padding: 10px;">
              <p>ลงชื่อ.......................................................... ผู้เสนอโครงการ</p>
              <p>(${proposal.responsiblePerson || '..........................................................'})</p>
              <p>ตำแหน่ง ${proposal.position || 'ครูผู้รับผิดชอบโครงการ'}</p>
              <p>วันที่ ..... เดือน .................... พ.ศ. .........</p>
            </td>
            <td style="width: 50%; border: none; text-align: center; vertical-align: top; padding: 10px;">
              <p>ลงชื่อ.......................................................... ผู้เห็นชอบโครงการ</p>
              <p>(..........................................................)</p>
              <p>ตำแหน่ง หัวหน้ากลุ่มงาน${proposal.department || ''}</p>
              <p>วันที่ ..... เดือน .................... พ.ศ. .........</p>
            </td>
          </tr>
          <tr style="border: none;">
            <td colspan="2" style="border: none; text-align: center; vertical-align: top; padding-top: 30px;">
              <p style="font-weight: bold;">คำอนุมัติของผู้อำนวยการสถานศึกษา</p>
              <p>[ &nbsp; ] อนุมัติ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; [ &nbsp; ] ไม่อนุมัติ เนื่องจาก ..............................................................</p>
              <br/>
              <p>ลงชื่อ.......................................................... ผู้อนุมัติโครงการ</p>
              <p>(${school.directorName})</p>
              <p>ตำแหน่ง ผู้อำนวยการโรงเรียน${school.name}</p>
              <p>วันที่ ..... เดือน .................... พ.ศ. .........</p>
            </td>
          </tr>
        </table>
      </div>
    </body>
    </html>
  `;

  const blob = new Blob(['\ufeff', htmlContent], {
    type: 'application/msword;charset=utf-8',
  });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  const safeTitle = (proposal.projectName || 'แบบเสนอโครงการ').replace(/[\/\\?%*:|"<>]/g, '_');
  link.download = `${safeTitle}_ปี${fiscalYear.year}.doc`;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

