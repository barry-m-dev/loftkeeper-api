/* Reset & Base */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.7;
background-color: #f0fdf4; margin: 0; padding: 0; color: #334155; -webkit-text-size-adjust: 100%; }
.container { width: 100%; max-width: 620px; margin: 0 auto; padding: 32px 16px; }
.cadre { background-color: #ffffff; border-radius: 16px; box-shadow: 0 8px 32px rgba(16, 185, 129, 0.15); overflow:
hidden; border: 1px solid rgba(16, 185, 129, 0.1); }

/* Header - Green & Purple Theme */
.header { background: linear-gradient(135deg, #10B981 0%, #3b82f6 50%, #8B5CF6 100%); padding: 16px 24px; text-align: left; }
.entete { display: flex; align-items: center; justify-content: flex-start; margin-bottom: 0; }
.logo { display: none; }
.app-name { color: #ffffff; font-weight: 700; font-size: 24px; margin: 0; letter-spacing: -0.5px; }
.validation-image { display: none; }

/* Content */
.msgContainer { padding: 40px 32px; background: #ffffff; }
.titre { color: #10B981; font-weight: 600; font-size: 20px; margin-bottom: 24px; text-align: center; letter-spacing:
-0.3px; }
.p1 { color: #475569; font-size: 15px; line-height: 1.7; margin: 12px 0; font-weight: 400; }
.salut { color: #8B5CF6; font-weight: 600; }
.highlight { color: #10B981; font-weight: 600; background: rgba(16, 185, 129, 0.1); padding: 3px 10px; border-radius:
6px; }

/* Info Box */
.info-box { background: #f0fdf4; border: 1px solid #86efac; border-radius: 12px; padding: 20px; margin: 20px 0; }
.info-box ul { list-style: none; padding: 0; margin: 0; }
.info-box li { padding: 8px 0; border-bottom: 1px solid #dcfce7; font-size: 14px; color: #475569; }
.info-box li:last-child { border-bottom: none; }
.info-box li strong { color: #334155; font-weight: 500; }

/* Credentials Box */
.credentials-box { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 2px solid #10B981;
border-radius: 12px; padding: 20px; margin: 24px 0; }
.credentials-box h4 { color: #10B981; font-size: 14px; font-weight: 600; margin-bottom: 12px; text-transform: uppercase;
letter-spacing: 0.5px; }
.credentials-box p { margin: 8px 0; font-size: 14px; color: #334155; }
.credentials-box .value { font-family: 'Courier New', monospace; background: white; padding: 4px 8px; border-radius:
4px; font-weight: 600; color: #10B981; }

/* Button */
.btn-container { margin: 28px 0; text-align: center; }
.btn { display: inline-block; padding: 14px 32px; font-size: 15px; color: #ffffff !important; text-decoration: none;
border-radius: 8px; background: linear-gradient(135deg, #10B981 0%, #059669 100%); box-shadow: 0 4px 14px rgba(16, 185,
129, 0.35); font-weight: 500; letter-spacing: 0.3px; }

/* Warning */
.warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 0 8px 8px 0; margin:
20px 0; font-size: 13px; color: #92400e; }

/* Footer */
.origin { margin-top: 32px; color: #64748b; font-size: 14px; text-align: center; border-top: 1px solid #e2e8f0;
padding-top: 24px; font-weight: 400; }
.footer { background: #f0fdf4; padding: 24px; text-align: center; border-top: 1px solid #dcfce7; }
.contact { background: linear-gradient(135deg, #10B981 0%, #059669 100%); padding: 20px; color: #ffffff; border-radius:
0 0 16px 16px; }
.contact p { margin: 4px 0; font-size: 13px; font-weight: 400; }
.contact a { color: #ffffff; text-decoration: underline; }
.contact small { display: block; margin-top: 8px; opacity: 0.85; font-size: 11px; }

/* Responsive */
@media screen and (max-width: 600px) {
.container { padding: 16px 12px !important; }
.msgContainer { padding: 24px 20px !important; }
.header { padding: 24px 16px !important; }
}