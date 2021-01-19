import {
    Component,
    OnInit,
    ViewChildren,
    ElementRef,
    QueryList,
    ViewChild,
} from "@angular/core";
import { MatSliderChange } from "@angular/material/slider";
import { Subject, Observable } from "rxjs";
import { debounceTime } from "rxjs/operators";
import { DocumentService, Type, DocumentConditions } from "../document.service";
import { FileUploadService } from "../file-upload.service";
import { MainService } from "../main.service";
import { PdfFile } from "@models/PdfFile";
import { Info } from "@models/Info";
import { NgForm } from "@angular/forms";
import { Router, ActivatedRoute } from '@angular/router';

@Component({
    selector: "app-add-document",
    templateUrl: "./add-document.component.html",
    styleUrls: ["./add-document.component.scss"],
})
export class AddDocumentComponent implements OnInit {
    public view: number = 0;
    public fileToUpload: File = null;
    public file: PdfFile = null;
    public radiusValueSubject = new Subject<{ Type: Type; Value: number }>();
    public loading: boolean = false;
    @ViewChild("infoForm") infoSelect: NgForm;
    @ViewChild("summaryForm") summaryForm: NgForm;

    constructor(
        public mainService: MainService,
        public documentService: DocumentService,
        private fileUploadService: FileUploadService,
        private router: Router,
        private route: ActivatedRoute
    ) {}

    ngOnInit(): void {
        this.radiusValueSubject
            .pipe(debounceTime(300))
            .subscribe((value: { Type: Type; Value: number }) => {
                switch (value.Type) {
                    case Type.Date: {
                        this.loading = true;
                        this.documentService
                            .findDates(this.file.Id, value.Value)
                            .subscribe((data: PdfFile) => {
                                this.loading = false;
                                this.documentService.documentConditions.Dates =
                                    data.Dates;
                            });
                        break;
                    }
                    case Type.Amount: {
                        this.loading = true;
                        this.documentService
                            .findAmounts(this.file.Id, value.Value)
                            .subscribe((data: PdfFile) => {
                                this.loading = false;
                                this.documentService.documentConditions.Amounts =
                                    data.Amounts;
                            });
                        break;
                    }
                    case Type.Info: {
                        this.loading = true;
                        this.documentService
                            .findInfo(this.file.Id, value.Value)
                            .subscribe((data: PdfFile) => {
                                this.loading = false;
                                this.documentService.documentConditions.Infos =
                                    data.Infos;
                            });
                        break;
                    }
                }
            });
    }

    public async nextStep() {
        switch (this.view) {
            case 0: {
                if (this.fileToUpload) {
                    this.loading = true;
                    await this.uploadFile();
                    this.documentService
                        .findDates(this.file.Id, 15)
                        .subscribe((data: PdfFile) => {
                            this.loading = false;
                            this.documentService.documentConditions.Dates =
                                data.Dates;
                            this.view += 1;
                        });
                }
                break;
            }
            case 1: {
                this.loading = true;
                this.documentService
                    .findAmounts(this.file.Id, 10)
                    .subscribe((data: PdfFile) => {
                        this.loading = false;
                        this.documentService.documentConditions.Amounts =
                            data.Amounts;
                        this.view += 1;
                    });

                break;
            }
            case 2: {
                this.loading = true;
                this.documentService
                    .findInfo(this.file.Id, 25)
                    .subscribe((data: PdfFile) => {
                        this.loading = false;
                        this.documentService.documentConditions.Infos =
                            data.Infos;
                        this.view += 1;
                    });
                break;
            }
            case 3: {
                this.infoSelect.form.markAllAsTouched();
                if (this.infoSelect.form.valid) {
                    this.view += 1;
                }
                break;
            }
            case 4: {
                this.summaryForm.form.markAllAsTouched();
                if (this.summaryForm.form.valid) {
                    this.loading = true;
                    await this.documentService
                        .saveDocument(
                            this.file.Id,
                            this.documentService.documentConditions
                        )
                        .toPromise();
                    this.loading = false;
                    this.view += 1;
                    setTimeout(() => {
                      this.router.navigate(['documents'], {queryParams: {id: this.documentService.documentConditions.Id}});
                    }, 1000);
                }
            }
        }
    }

    public previousStep(): void {
        this.view -= 1;
    }

    public handleFileInput(files: FileList) {
        this.fileToUpload = files.item(0);
    }

    public async uploadFile(): Promise<PdfFile> {
        const response: PdfFile = await this.fileUploadService
            .postFile(this.fileToUpload)
            .toPromise();
        this.file = response;
        this.documentService.documentConditions = new DocumentConditions();
        this.documentService.documentConditions.Id = this.file.Id;
        this.documentService.documentConditions.Name = this.file.Name;
        return response;
    }

    public onSlide(event: MatSliderChange, type: Type) {
        this.radiusValueSubject.next({ Type: type, Value: event.value });
    }

    public infoValue(info: Info) {
        return info.Nearby.filter((v, i) => info.Selected?.includes(i)).reduce(
            (p, c, i, a) => p + " " + c,
            ""
        );
    }
}
