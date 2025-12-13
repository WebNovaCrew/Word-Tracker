import { Component, Input, Output, EventEmitter, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../../../environments/environment';

@Component({
    selector: 'app-plan-editor-calendar',
    standalone: true,
    imports: [CommonModule],
    templateUrl: './plan-editor-calendar.component.html',
    styleUrls: ['./plan-editor-calendar.component.scss']
})
export class PlanEditorCalendarComponent implements OnChanges {
    @Input() planData: any;
    @Output() dateSelected = new EventEmitter<string>();

    schedule: any[] = [];
    loading = false;

    // Calendar View State
    viewDate: Date = new Date();
    calendarDays: any[] = [];
    weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    constructor(private http: HttpClient) { }

    ngOnInit() {
        this.generateCalendar();
    }

    ngOnChanges(changes: SimpleChanges) {
        if (this.planData && (
            changes['planData'] ||
            this.planData.totalWordCount ||
            this.planData.startDate ||
            this.planData.endDate ||
            this.planData.algorithmType ||
            this.planData.strategyIntensity
        )) {
            // Update view date to start date if available
            if (this.planData.startDate) {
                const start = new Date(this.planData.startDate);
                if (!isNaN(start.getTime())) {
                    this.viewDate = start;
                }
            }
            this.generateCalendar(); // Render grid immediately
            this.fetchPreview();
        }
    }

    fetchPreview() {
        if (!this.planData.startDate || !this.planData.endDate) return;

        this.loading = true;
        const payload = {
            total_word_count: this.planData.totalWordCount,
            start_date: this.planData.startDate,
            end_date: this.planData.endDate,
            algorithm_type: this.planData.algorithmType,
            strategy_intensity: this.planData.strategyIntensity,
            weekend_rule: this.planData.weekendRule
        };

        this.http.post(`${environment.apiUrl}/preview_plan.php`, payload)
            .subscribe({
                next: (res: any) => {
                    if (res.success) {
                        this.schedule = res.data;
                        this.generateCalendar();
                    }
                    this.loading = false;
                },
                error: (err) => {
                    console.error('Preview error', err);
                    this.loading = false;
                }
            });
    }

    generateCalendar() {
        // Generate grid for viewDate month
        const year = this.viewDate.getFullYear();
        const month = this.viewDate.getMonth();
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);

        let startDayOfWeek = firstDay.getDay() - 1;
        if (startDayOfWeek === -1) startDayOfWeek = 6;

        this.calendarDays = [];

        // Padding prev month
        for (let i = 0; i < startDayOfWeek; i++) {
            this.calendarDays.push({ empty: true });
        }

        // Days
        for (let i = 1; i <= lastDay.getDate(); i++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
            const scheduleDay = this.schedule.find(d => d.date === dateStr);

            this.calendarDays.push({
                day: i,
                date: dateStr,
                target: scheduleDay ? scheduleDay.target : 0,
                hasTarget: !!scheduleDay
            });
        }
    }

    prevMonth() {
        this.viewDate = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth() - 1, 1);
        this.generateCalendar();
    }

    nextMonth() {
        this.viewDate = new Date(this.viewDate.getFullYear(), this.viewDate.getMonth() + 1, 1);
        this.generateCalendar();
    }

    get monthName(): string {
        return this.viewDate.toLocaleString('default', { month: 'long', year: 'numeric' });
    }

    onDayClick(date: string) {
        if (date) {
            this.dateSelected.emit(date);
        }
    }
}
