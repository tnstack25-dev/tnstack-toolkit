export const websites = [
  { domain: 'tnstack.net', url: 'https://tnstack.net', uptime: '99.98%', ssl: 'Còn 45 ngày', version: '6.5.2', checked: '2 phút trước', tone: 'green' },
  { domain: 'demo.tnstack.net', url: 'https://demo.tnstack.net', uptime: '98.56%', ssl: 'Còn 12 ngày', version: '6.4.3', checked: '1 phút trước', tone: 'green' },
  { domain: 'blog.tnstack.net', url: 'https://blog.tnstack.net', uptime: '72.41%', ssl: 'Hết hạn', version: '6.3.1', checked: '1 phút trước', tone: 'red' },
  { domain: 'shop.tnstack.net', url: 'https://shop.tnstack.net', uptime: '99.12%', ssl: 'Còn 85 ngày', version: '6.5.2', checked: '2 phút trước', tone: 'green' },
  { domain: 'test.tnstack.net', url: 'https://test.tnstack.net', uptime: '100%', ssl: 'Còn 120 ngày', version: '6.5.2', checked: '3 phút trước', tone: 'green' },
]

export const logs = [
  { type: 'LỖI', text: 'Không thể kết nối đến database', site: 'blog.tnstack.net', time: '10:25 AM', tone: 'red' },
  { type: 'CẢNH BÁO', text: 'SSL sẽ hết hạn trong 12 ngày', site: 'demo.tnstack.net', time: '09:15 AM', tone: 'yellow' },
  { type: 'LỖI', text: 'HTTP 500 Internal Server Error', site: 'blog.tnstack.net', time: '08:45 AM', tone: 'red' },
  { type: 'CẢNH BÁO', text: 'WordPress phiên bản cũ', site: 'test.tnstack.net', time: '07:30 AM', tone: 'yellow' },
  { type: 'THÔNG TIN', text: 'Backup hoàn thành', site: 'tnstack.net', time: '06:20 AM', tone: 'blue' },
]
