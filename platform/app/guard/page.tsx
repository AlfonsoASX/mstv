import { MapPin, LogIn, LogOut, AlertTriangle, Clock } from "lucide-react"
import Link from "next/link"

export default function GuardDashboard() {
    return (
        <div className="space-y-6">
            <header className="flex justify-between items-center">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900">Hello, Officer</h1>
                    <p className="text-slate-500 text-sm">Ready for your shift?</p>
                </div>
                <div className="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold">
                    JD
                </div>
            </header>

            <div className="card bg-indigo-600 text-white border-none shadow-lg shadow-indigo-200">
                <div className="flex items-start justify-between mb-4">
                    <div>
                        <p className="text-indigo-100 text-sm font-medium mb-1">Next Shift</p>
                        <h2 className="text-xl font-bold">Main HQ - North Gate</h2>
                    </div>
                    <div className="bg-white/20 p-2 rounded-lg">
                        <MapPin className="w-5 h-5 text-white" />
                    </div>
                </div>
                <div className="flex items-center gap-2 text-indigo-100 text-sm">
                    <Clock className="w-4 h-4" />
                    <span>Today, 08:00 AM - 04:00 PM</span>
                </div>
            </div>

            <div>
                <h3 className="text-lg font-bold text-slate-900 mb-3">Quick Actions</h3>
                <div className="grid grid-cols-2 gap-4">
                    <Link href="/guard/checkin?type=IN" className="card p-4 flex flex-col items-center justify-center gap-2 active:scale-95 transition-transform border-indigo-100 bg-indigo-50/50">
                        <div className="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 mb-1">
                            <LogIn className="w-6 h-6" />
                        </div>
                        <span className="font-semibold text-slate-900">Check In</span>
                    </Link>

                    <Link href="/guard/checkin?type=OUT" className="card p-4 flex flex-col items-center justify-center gap-2 active:scale-95 transition-transform border-slate-200">
                        <div className="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 mb-1">
                            <LogOut className="w-6 h-6" />
                        </div>
                        <span className="font-semibold text-slate-900">Check Out</span>
                    </Link>

                    <Link href="/guard/incident" className="card p-4 flex flex-col items-center justify-center gap-2 active:scale-95 transition-transform border-amber-100 bg-amber-50/50 col-span-2">
                        <div className="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 mb-1">
                            <AlertTriangle className="w-6 h-6" />
                        </div>
                        <span className="font-semibold text-slate-900">Report Incident</span>
                    </Link>
                </div>
            </div>

            <div>
                <h3 className="text-lg font-bold text-slate-900 mb-3">Recent Activity</h3>
                <div className="space-y-3">
                    <div className="card p-3 flex items-center gap-3">
                        <div className="w-2 h-2 rounded-full bg-emerald-500" />
                        <div className="flex-1">
                            <p className="text-sm font-medium text-slate-900">Shift Completed</p>
                            <p className="text-xs text-slate-500">Yesterday, 04:00 PM</p>
                        </div>
                    </div>
                    <div className="card p-3 flex items-center gap-3">
                        <div className="w-2 h-2 rounded-full bg-indigo-500" />
                        <div className="flex-1">
                            <p className="text-sm font-medium text-slate-900">Check In</p>
                            <p className="text-xs text-slate-500">Yesterday, 08:00 AM</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    )
}
