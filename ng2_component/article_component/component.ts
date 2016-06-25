import { Component, ElementRef } from '@angular/core';

@Component({
    moduleId: __moduleName,
    selector: 'article-component',
    styles: [`
        .article-wrapper {
           border: 2px dashed silver;
           text-align: center;
        }
        .article-content {
            font-size: x-large;
        }
    `],
    template: `
        <div class='article-wrapper' [ngStyle]="{'background-image': 'url(' + photo + ')'}">
          <h1>{{heading}}</h1>
          <div class="article-content" [innerHTML]="value">
          </div>
        </div>
    `
})
export class ArticleComponent {
    // We would like to define default values.
    public heading: string = '';
    public value: string = '';
    public photo: string = '';

    constructor(private _ElementRef: ElementRef) {
        // elRef.nativeElement.id
        let properties = drupalSettings.pdb.ng2.components[_ElementRef.nativeElement.id].properties;
        for(var key in properties) {
            this[key] = properties[key];
        }
    }
}
