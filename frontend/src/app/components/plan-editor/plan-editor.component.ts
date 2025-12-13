import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { Router, ActivatedRoute } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { PlanEditorCalendarComponent } from './components/plan-editor-calendar/plan-editor-calendar.component';
import { environment } from '../../../environments/environment';

import { StatsComponent } from '../stats/stats.component';
import { PlanEditorProgressComponent } from './components/plan-editor-progress/plan-editor-progress.component';

@Component({
    selector: 'app-plan-editor',
    standalone: true,
    imports: [CommonModule, FormsModule, ReactiveFormsModule, PlanEditorCalendarComponent, StatsComponent, PlanEditorProgressComponent],
    templateUrl: './plan-editor.component.html',
    styleUrls: ['./plan-editor.component.scss']
})
export class PlanEditorComponent implements OnInit {
    // Plan Data
    planTitle = 'Edit me: A Brand New Goal!';
    planSubtitle = 'Novella Drafting';

    activeTab = 'schedule'; // schedule, progress, stats

    // Data for child components (shared state)
    planData: any = {
        title: '',
        subtitle: '',
        activity: 'writing',
        content: 'novel',
        description: '',
        isPrivate: false,
        totalWordCount: 50000,
        startDate: new Date().toISOString().split('T')[0],
        endDate: '',
        algorithmType: 'steady',
        targetUnit: 'words',
        targetType: 'overall',
        strategyIntensity: 'average',
        weekendRule: 'none',
        customRules: {},
        displaySettings: {
            viewAs: 'calendar',
            weekBegins: 'monday',
            groupBy: 'day',
            color: '#3b82f6'
        },
        progressSettings: {
            changeBehavior: 'recalculate',
            recordingMode: 'overall'
        }
    };

    constructor(
        private http: HttpClient,
        private router: Router,
        private route: ActivatedRoute
    ) { }

    ngOnInit() {
        this.route.params.subscribe(params => {
            if (params['id']) {
                this.loadPlan(+params['id']);
            } else {
                this.initNewPlan();
            }
        });
    }

    initNewPlan() {
        const today = new Date();
        const nextMonth = new Date();
        nextMonth.setMonth(today.getMonth() + 1);
        this.planData.startDate = today.toISOString().split('T')[0];
        this.planData.endDate = nextMonth.toISOString().split('T')[0];
    }

    loadPlan(id: number) {
        this.http.get<any>(`${environment.apiUrl}/get_plan.php?id=${id}`)
            .subscribe({
                next: (plan) => {
                    if (plan) {
                        this.planData['id'] = plan.id;
                        this.planTitle = plan.name;
                        this.planData.title = plan.name;
                        this.planData.content = plan.content_type;
                        this.planData.activity = plan.activity_type;
                        this.planData.startDate = plan.start_date;
                        this.planData.endDate = plan.end_date;
                        this.planData.totalWordCount = plan.goal_amount;
                        this.planData.algorithmType = plan.strategy;
                        this.planData.strategyIntensity = plan.intensity;
                        if (plan.display_settings) {
                            try {
                                const settings = JSON.parse(plan.display_settings);
                                this.planData.displaySettings = { ...this.planData.displaySettings, ...settings };
                            } catch (e) {
                                console.error('Error parsing settings', e);
                            }
                        }
                    }
                },
                error: (err) => {
                    console.error('Failed to load plan:', err);
                    alert(err.status === 0 ? 'Network error. Please check your connection.' : 'Failed to load plan');
                }
            });
    }

    savePlan() {
        const userId = localStorage.getItem('user_id');

        if (!userId) {
            alert('Please log in first.');
            this.router.navigate(['/login']);
            return;
        }

        // Validate required fields
        if (!this.planTitle || !this.planData.totalWordCount || !this.planData.startDate || !this.planData.endDate) {
            alert('Please fill in all required fields: Plan Name, Goal Amount, Start Date, and End Date.');
            return;
        }

        const payload: any = {
            user_id: userId,
            name: this.planTitle,
            content_type: this.planData.content || 'Novel',
            activity_type: this.planData.activity || 'Writing',
            start_date: this.planData.startDate,
            end_date: this.planData.endDate,
            goal_amount: this.planData.totalWordCount,
            strategy: this.planData.algorithmType || 'steady',
            intensity: this.planData.strategyIntensity || 'average',
            display_settings: this.planData.displaySettings
        };

        if (this.planData.id) {
            // Update
            payload.id = this.planData.id;
            this.http.post(`${environment.apiUrl}/update_plan.php`, payload)
                .subscribe({
                    next: (res: any) => {
                        alert(res.message || 'Plan updated successfully!');
                        this.router.navigate(['/plans']);
                    },
                    error: (err) => {
                        console.error('Error updating plan:', err);
                        alert(err.status === 0 ? 'Network error. Please check your connection.' : 'Error updating plan.');
                    }
                });
        } else {
            // Create
            this.http.post(`${environment.apiUrl}/create_plan.php`, payload)
                .subscribe({
                    next: (res: any) => {
                        if (res.success) {
                            alert(res.message || 'Plan saved successfully!');
                            this.router.navigate(['/plans']);
                        } else {
                            alert(res.message || 'Error saving plan.');
                        }
                    },
                    error: (err) => {
                        console.error('Plan save error:', err);
                        alert(err.status === 0 ? 'Network error. Please check your connection.' : (err.error?.message || 'Error saving plan.'));
                    }
                });
        }
    }

    onCalendarDateSelect(date: string) {
        // Intelligent date logic:
        // 1. If start date is missing, set start date.
        // 2. If end date is missing, set end date.
        // 3. If clicking before start date, update start date.
        // 4. If clicking after end date, update end date.
        // 5. If clicking "inside", assume they want to change end date (or maybe prompt? default to end for now).

        if (!this.planData.startDate) {
            this.planData.startDate = date;
            return;
        }

        if (!this.planData.endDate) {
            // Ensure end date is after start date
            if (date < this.planData.startDate) {
                this.planData.endDate = this.planData.startDate;
                this.planData.startDate = date;
            } else {
                this.planData.endDate = date;
            }
            return;
        }

        const clicked = new Date(date).getTime();
        const start = new Date(this.planData.startDate).getTime();
        const end = new Date(this.planData.endDate).getTime();

        // If clicked exactly on start or end, do nothing or maybe toggle?
        if (clicked === start) {
            // Maybe they want to "unset" start? But we need a start.
            // Let's assume they might be correcting the start date, so just set it.
            this.planData.startDate = date;
            return;
        }
        if (clicked === end) {
            this.planData.endDate = date;
            return;
        }

        if (clicked < start) {
            this.planData.startDate = date;
        } else if (clicked > end) {
            this.planData.endDate = date;
        } else {
            // Inside the range.
            // Calculate distance to determine which one to update
            const distToStart = Math.abs(clicked - start);
            const distToEnd = Math.abs(clicked - end);

            if (distToStart < distToEnd) {
                this.planData.startDate = date;
            } else {
                this.planData.endDate = date;
            }
        }
    }
}
