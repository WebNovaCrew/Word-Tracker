import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { Router, RouterLink } from '@angular/router';
import { environment } from '../../../environments/environment';

@Component({
    selector: 'app-create-checklist',
    standalone: true,
    imports: [CommonModule, FormsModule, RouterLink],
    templateUrl: './create-checklist.component.html',
    styleUrls: ['./create-checklist.component.scss']
})
export class CreateChecklistComponent implements OnInit {
    checklistName: string = '';
    items: { text: string; is_done: boolean }[] = [];
    newItemText: string = '';
    planId: number | null = null;

    constructor(private http: HttpClient, private router: Router) { }

    ngOnInit() {
        // Check if user is logged in
        if (!localStorage.getItem('user_id')) {
            this.router.navigate(['/login']);
        }

        // Add initial empty item
        this.addItem();
    }

    addItem() {
        this.items.push({ text: '', is_done: false });
    }

    removeItem(index: number) {
        this.items.splice(index, 1);
    }

    moveUp(index: number) {
        if (index > 0) {
            const temp = this.items[index];
            this.items[index] = this.items[index - 1];
            this.items[index - 1] = temp;
        }
    }

    moveDown(index: number) {
        if (index < this.items.length - 1) {
            const temp = this.items[index];
            this.items[index] = this.items[index + 1];
            this.items[index + 1] = temp;
        }
    }

    saveChecklist() {
        const userId = localStorage.getItem('user_id');

        if (!this.checklistName) {
            alert('Please enter a checklist name.');
            return;
        }

        // Filter out empty items
        const validItems = this.items.filter(item => item.text.trim() !== '');

        if (validItems.length === 0) {
            alert('Please add at least one item to the checklist.');
            return;
        }

        const payload = {
            user_id: userId,
            plan_id: this.planId,
            name: this.checklistName,
            items: validItems
        };

        console.log('Saving checklist:', payload);

        this.http.post(`${environment.apiUrl}/create_checklist.php`, payload)
            .subscribe({
                next: (res: any) => {
                    if (res.success) {
                        alert(res.message || 'Checklist created successfully!');
                        this.router.navigate(['/my-checklists']);
                    } else {
                        alert(res.message || 'Error creating checklist.');
                    }
                },
                error: (err) => {
                    console.error('Checklist save error:', err);
                    alert(err.status === 0 ? 'Network error. Please check your connection.' : (err.error?.message || 'Error creating checklist. Please try again.'));
                }
            });
    }
}
