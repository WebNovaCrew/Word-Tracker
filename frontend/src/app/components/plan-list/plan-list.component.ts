import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { Router, RouterModule, ActivatedRoute } from '@angular/router';
import { ContentLoaderComponent } from '../content-loader/content-loader.component';
import { environment } from '../../../environments/environment';

@Component({
    selector: 'app-plan-list',
    standalone: true,
    imports: [CommonModule, FormsModule, RouterModule, ContentLoaderComponent],
    templateUrl: './plan-list.component.html',
    styleUrls: ['./plan-list.component.scss']
})
export class PlanListComponent implements OnInit {
    plans: any[] = [];
    filteredPlans: any[] = [];
    isLoading: boolean = true;

    // Controls
    searchTerm: string = '';
    pageSize: number = 10;
    currentPage: number = 1;
    sortColumn: string = 'created_at';
    sortDirection: 'asc' | 'desc' = 'desc';

    // Pagination Metadata
    totalItems: number = 0;
    totalPages: number = 1;

    // Stats for header (mock or calculated)
    userName: string = 'User'; // Replace with actual user name if available

    constructor(private http: HttpClient, private router: Router, private route: ActivatedRoute) { }

    currentFilter = 'active'; // 'active' or 'archived'

    ngOnInit() {
        this.route.queryParams.subscribe(params => {
            this.currentFilter = params['filter'] === 'archived' ? 'archived' : 'active';
            this.currentPage = 1;
            this.fetchPlans();
        });

        // Get user name from local storage or service
        const storedUser = localStorage.getItem('user_name');
        if (storedUser) this.userName = storedUser;
    }

    fetchPlans() {
        const userId = localStorage.getItem('user_id');
        if (!userId) {
            this.isLoading = false;
            return;
        }

        this.isLoading = true;

        // Prepare params
        const isArchived = this.currentFilter === 'archived';

        // Call API with pagination and filter
        const url = `${environment.apiUrl}/get_plans.php?user_id=${userId}&page=${this.currentPage}&limit=${this.pageSize}&is_archived=${isArchived}`;

        this.http.get<any>(url).subscribe({
            next: (res) => {
                this.plans = res.records || [];

                // Update Pagination Metadata from API
                if (res.pagination) {
                    this.totalItems = res.pagination.total_items;
                    // Ensure totalPages is at least 1
                    this.totalPages = Math.max(1, res.pagination.total_pages);
                } else {
                    // Fallback
                    this.totalItems = this.plans.length;
                    this.totalPages = 1;
                }

                // We don't slice plans anymore, but we might want to sort them client-side if the user clicks headers
                this.applyFilters();
                this.isLoading = false;
            },
            error: (err) => {
                console.error('Error fetching plans:', err);
                this.isLoading = false;
            }
        });
    }

    applyFilters() {
        let temp = [...this.plans];

        // Search (Client-side on current page only)
        if (this.searchTerm) {
            const term = this.searchTerm.toLowerCase();
            temp = temp.filter(p =>
                p.plan_name.toLowerCase().includes(term) ||
                (p.content_type && p.content_type.toLowerCase().includes(term))
            );
        }

        // Sort (Client-side on current page only)
        temp.sort((a, b) => {
            let valA = a[this.sortColumn];
            let valB = b[this.sortColumn];

            // Handle dates and numbers
            if (!isNaN(Number(valA)) && !isNaN(Number(valB))) {
                valA = Number(valA);
                valB = Number(valB);
            } else {
                valA = valA ? valA.toString().toLowerCase() : '';
                valB = valB ? valB.toString().toLowerCase() : '';
            }

            if (valA < valB) return this.sortDirection === 'asc' ? -1 : 1;
            if (valA > valB) return this.sortDirection === 'asc' ? 1 : -1;
            return 0;
        });

        this.filteredPlans = temp;
    }

    onSearch() {
        // Search happens locally on the fetched page for now
        this.applyFilters();
    }

    // Called when user changes page size
    onPageSizeChange() {
        this.currentPage = 1;
        this.fetchPlans();
    }

    sort(column: string) {
        if (this.sortColumn === column) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = column;
            this.sortDirection = 'asc';
        }
        this.applyFilters();
    }

    get paginatedPlans() {
        // With server-side pagination, 'filteredPlans' already contains only the items for this page.
        // We just return it directly.
        return this.filteredPlans;
    }

    // totalPages property is now managed by fetchPlans (from API)
    // We declare a property for it at the top of the class

    nextPage() {
        if (this.currentPage < this.totalPages) {
            this.currentPage++;
            this.fetchPlans();
        }
    }

    prevPage() {
        if (this.currentPage > 1) {
            this.currentPage--;
            this.fetchPlans();
        }
    }

    updateColor(plan: any, event: any) {
        const newColor = event.target.value;
        plan.color_code = newColor; // Optimistic update

        this.http.post(`${environment.apiUrl}/update_plan_color.php`, {
            id: plan.id,
            color: newColor
        }).subscribe({
            error: (err) => {
                console.error('Failed to update color', err);
                // Revert?
            }
        });
    }


    // Confirmation Modal
    showConfirmation: boolean = false;
    confirmationTitle: string = '';
    confirmationMessage: string = '';
    confirmationActionType: 'delete' | 'archive' | 'unarchive' | null = null;
    pendingPlan: any = null;

    archivePlan(plan: any) {
        this.pendingPlan = plan;
        this.confirmationActionType = 'archive';
        this.confirmationTitle = 'Archive Plan';
        this.confirmationMessage = `Are you sure you want to archive "${plan.plan_name}"? It will be moved to the archives tab.`;
        this.showConfirmation = true;
    }

    deletePlan(plan: any) {
        this.pendingPlan = plan;
        this.confirmationActionType = 'delete';
        this.confirmationTitle = 'Delete Plan';
        this.confirmationMessage = `Are you sure you want to permanently delete "${plan.plan_name}"? This action cannot be undone.`;
        this.showConfirmation = true;
    }

    cancelConfirmation() {
        this.showConfirmation = false;
        this.pendingPlan = null;
        this.confirmationActionType = null;
    }

    unarchivePlan(plan: any) {
        this.pendingPlan = plan;
        this.confirmationActionType = 'unarchive';
        this.confirmationTitle = 'Unarchive Plan';
        this.confirmationMessage = `Are you sure you want to restore "${plan.plan_name}" to your active plans?`;
        this.showConfirmation = true;
    }

    confirmAction() {
        if (!this.pendingPlan || !this.confirmationActionType) return;

        if (this.confirmationActionType === 'archive') {
            this.executeArchive(this.pendingPlan);
        } else if (this.confirmationActionType === 'unarchive') {
            this.executeUnarchive(this.pendingPlan);
        } else if (this.confirmationActionType === 'delete') {
            this.executeDelete(this.pendingPlan);
        }

        this.cancelConfirmation();
    }

    private executeArchive(plan: any) {
        console.log('Sending archive request for ID:', plan.id);
        this.http.post(`${environment.apiUrl}/archive_plan.php`, { id: plan.id, archive: true })
            .subscribe({
                next: (res) => {
                    console.log('Archive successful', res);
                    plan.is_archived = true;
                    plan.status = 'archived';
                    this.applyFilters();
                },
                error: (err) => console.error('Failed to archive', err)
            });
    }

    private executeUnarchive(plan: any) {
        console.log('Sending unarchive request for ID:', plan.id);
        this.http.post(`${environment.apiUrl}/archive_plan.php`, { id: plan.id, archive: false })
            .subscribe({
                next: (res) => {
                    console.log('Unarchive successful', res);
                    plan.is_archived = false;
                    plan.status = 'In Progress'; // Or calculate based on date
                    this.applyFilters();
                },
                error: (err) => console.error('Failed to unarchive', err)
            });
    }

    private executeDelete(plan: any) {
        console.log('Sending delete request for ID:', plan.id);
        this.http.post(`${environment.apiUrl}/delete_plan.php`, { id: plan.id })
            .subscribe({
                next: (res) => {
                    console.log('Delete successful', res);
                    this.plans = this.plans.filter(p => p.id !== plan.id);
                    this.applyFilters();
                },
                error: (err) => console.error('Failed to delete', err)
            });
    }

    openPlan(planId: number) {
        this.router.navigate(['/plan-editor', planId]); // Assuming we have routing set up
    }

    createNewPlan() {
        this.router.navigate(['/create-plan']);
    }
}
